<?php

/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Controle « API Saturne publiee » de la CI des modules (Evarisk/Saturne#1844).
 *
 * Une fonction, une methode publique, une classe ou un fichier ajoutes a Saturne `develop` depuis
 * son dernier tag n'existent chez aucun client : le zip publie ne les contient pas. Un module qui
 * s'en sert passe pourtant sa CI au vert, puisque PHPStan l'analyse contre Saturne `develop`. Le
 * decalage n'apparaissait qu'a la release, ou chez le client (Digirisk 23.2.0 et 23.2.1, DoliMeet
 * 23.1.3 suspendue le 12/10 sur fetchUserSignatory).
 *
 * Le script compare les deux arbres de Saturne, cherche les appels dans le module et, sur une pull
 * request, separe ceux que la PR introduit de ceux que la branche de base portait deja. Tout est lu
 * par git (ls-tree, cat-file) : aucun checkout du tag, et le module se lit tel que commite. Le code
 * est decoupe par le tokenizer de PHP, pas par expression reguliere : un nom cite dans un
 * commentaire ou une chaine ne compte pas comme un appel.
 *
 * Usage :
 *   php dev/ci/published-api.php --saturne=<depot Saturne> --module=<depot du module>
 *       [--saturne-ref=HEAD] [--published=<tag>] [--head=HEAD] [--base=<revision>] [--name=<module>]
 *
 * --published vaut par defaut le plus grand tag X.Y.Z du depot Saturne ; --base, donne sur une PR,
 * active la separation « introduit par la PR » / « deja sur la base ».
 *
 * Sortie : annotations GitHub (::warning), resume Markdown dans $GITHUB_STEP_SUMMARY et sur la
 * sortie standard. Code retour 1 si la PR introduit un appel, 2 sur une erreur.
 */

/**
 * Comparaison de l'API de Saturne develop a sa derniere version publiee, et appels du module.
 */
final class SaturnePublishedApiCheck
{
    /**
     * Dossiers de Saturne exclus du zip (export-ignore) : ce qu'ils declarent n'est pas de l'API.
     * dev/phan/stubs.php y redeclare d'ailleurs des classes du coeur.
     */
    private const SATURNE_IGNORED = ['.github/', '.phan/', '.tx/', 'dev/', 'doc/', 'node_modules/', 'tests/', 'vendor/'];

    /**
     * Dossiers du module qui ne partent pas chez le client.
     */
    private const MODULE_IGNORED = ['.github/', 'dev/', 'node_modules/', 'test/', 'tests/', 'vendor/'];

    /**
     * Fonctions dont l'argument chaine protege un appel : `if (function_exists('x')) x();` reste sur,
     * comme `if (file_exists(dol_buildpath('/saturne/...')))` devant une inclusion.
     */
    private const GUARDS = ['class_exists', 'enum_exists', 'file_exists', 'function_exists', 'interface_exists', 'is_callable', 'method_exists', 'trait_exists'];

    /**
     * Chemin d'un fichier de Saturne dans une chaine : '/saturne/core/tpl/medias/x.tpl.php'.
     */
    private const FILE_PATTERN = '#/saturne/([A-Za-z0-9_./-]+\.php)#';

    /**
     * Libelles des quatre sortes de symboles.
     */
    private const KIND_LABELS = ['function' => 'fonction', 'method' => 'méthode', 'class' => 'classe', 'file' => 'fichier'];

    /**
     * Nombre d'emplacements cites par symbole dans le resume.
     */
    private const MAX_PLACES = 6;

    /**
     * @var array<string, string> Options de la ligne de commande
     */
    private array $options;

    /**
     * @var list<string> Lignes d'annotations GitHub, emises a la fin
     */
    private array $annotations = [];

    /**
     * Constructor.
     *
     * @param array<string, string> $options Options --cle=valeur de la ligne de commande
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Run the check and return the process exit code.
     *
     * @return int 0 rien d'introduit, 1 appel introduit par la PR
     */
    public function run(): int
    {
        $saturneDir   = $this->option('saturne');
        $moduleDir    = $this->option('module');
        $moduleName   = $this->options['name'] ?? basename((string) realpath($moduleDir));
        $saturneRef   = self::revParse($saturneDir, $this->options['saturne-ref'] ?? 'HEAD');
        $published    = $this->options['published'] ?? self::latestTag($saturneDir);
        $publishedSha = self::revParse($saturneDir, $published);
        $head         = self::revParse($moduleDir, $this->options['head'] ?? 'HEAD');
        $base         = ($this->options['base'] ?? '') !== '' ? self::revParse($moduleDir, $this->options['base']) : null;

        $fresh = self::freshApi($saturneDir, $saturneRef, $publishedSha);

        $reader   = new SaturnePublishedApiBlobReader($moduleDir);
        $analyses = [];
        foreach (self::phpFiles($moduleDir, $head, self::MODULE_IGNORED) as $file) {
            $analyses[$file] = self::analyse((string) $reader->read($head, $file));
        }
        $ambiguous = self::ambiguous($fresh, $analyses);

        $headHits = [];
        foreach ($analyses as $file => $analysis) {
            $headHits[$file] = self::hits($analysis, $fresh, $ambiguous);
        }

        $introduced = [];
        $addedLines = [];
        if ($base !== null) {
            [$introduced, $addedLines] = self::introducedSymbols($moduleDir, $reader, $base, $head, $headHits, $fresh, $ambiguous);
        }
        $reader->close();

        $report = $this->report($moduleName, $published, $saturneRef, $fresh, $ambiguous, $headHits, $base, $introduced, $addedLines);

        foreach ($this->annotations as $line) {
            echo $line . "\n";
        }
        $summaryFile = getenv('GITHUB_STEP_SUMMARY');
        if (is_string($summaryFile) && $summaryFile !== '') {
            file_put_contents($summaryFile, $report, FILE_APPEND);
        }
        echo "\n" . $report;

        if (!empty($introduced)) {
            $names = array_map(fn (string $id): string => $fresh[$id]['name'], array_keys($introduced));
            echo '::error title=API Saturne non publiée::Cette PR appelle de l\'API absente de Saturne ' . $published . ' : ' . implode(', ', $names) . ". Publier Saturne avant la release du module.\n";
            return 1;
        }
        return 0;
    }

    /**
     * Return a mandatory option or stop.
     *
     * @param  string $name Option name
     * @return string       Option value
     */
    private function option(string $name): string
    {
        if (($this->options[$name] ?? '') === '') {
            throw new InvalidArgumentException('option --' . $name . ' manquante');
        }
        return $this->options[$name];
    }

    /**
     * Public API present in Saturne at $ref and absent from the published tag.
     *
     * @param  string $dir       Saturne git directory
     * @param  string $ref       Analysed commit (develop)
     * @param  string $published Published tag commit
     * @return array<string, array{name: string, where: string}> Cle « sorte:nom », nom affiche et lieu de definition
     */
    private static function freshApi(string $dir, string $ref, string $published): array
    {
        $reader  = new SaturnePublishedApiBlobReader($dir);
        $current = self::definitions($reader, $ref, self::phpFiles($dir, $ref, self::SATURNE_IGNORED));
        $old     = self::definitions($reader, $published, self::phpFiles($dir, $published, self::SATURNE_IGNORED));
        $reader->close();

        $fresh = [];
        foreach (['function', 'class', 'file'] as $kind) {
            foreach ($current[$kind] as $key => $def) {
                if (!isset($old[$kind][$key])) {
                    $fresh[$kind . ':' . $key] = $def;
                }
            }
        }
        // Une methode existait deja si une classe publiee, quelle qu'elle soit et quelle que soit sa
        // visibilite, la declarait : a l'appel ($obj->nom()), seul le nom est connu, pas le type de $obj.
        foreach ($current['method'] as $key => $def) {
            if (!isset($old['methodAny'][$key])) {
                $fresh['method:' . $key] = $def;
            }
        }
        ksort($fresh);
        return $fresh;
    }

    /**
     * Collect the definitions of a tree.
     *
     * @param  SaturnePublishedApiBlobReader $reader Blob reader
     * @param  string                        $ref    Commit
     * @param  list<string>                  $files  PHP files of the tree
     * @return array{function: array<string, array{name: string, where: string}>, method: array<string, array{name: string, where: string}>, methodAny: array<string, true>, class: array<string, array{name: string, where: string}>, file: array<string, array{name: string, where: string}>}
     */
    private static function definitions(SaturnePublishedApiBlobReader $reader, string $ref, array $files): array
    {
        $defs = ['function' => [], 'method' => [], 'methodAny' => [], 'class' => [], 'file' => []];
        foreach ($files as $file) {
            $defs['file'][$file] = ['name' => $file, 'where' => ''];
            $code = $reader->read($ref, $file);
            if ($code === null) {
                continue;
            }
            $analysis = self::analyse($code);
            foreach ($analysis['functions'] as $key => $name) {
                $defs['function'][$key] ??= ['name' => $name . '()', 'where' => $file];
            }
            foreach ($analysis['classes'] as $key => $name) {
                $defs['class'][$key] ??= ['name' => $name, 'where' => $file];
            }
            foreach ($analysis['methods'] as $method) {
                $key = strtolower($method['name']);
                $defs['methodAny'][$key] = true;
                if ($method['public']) {
                    $defs['method'][$key] ??= ['name' => $method['name'] . '()', 'where' => $method['class'] . ', ' . $file];
                }
            }
        }
        return $defs;
    }

    /**
     * Unpublished method names that a call cannot be pinned to Saturne by its name alone.
     *
     * `$date->getTimestamp()` est un appel a DateTime, pas a SaturneXlsxExport::getTimestamp() ;
     * `$this->fetchRecords()` dans une classe du module qui declare fetchRecords() appelle la sienne.
     * Ces noms sont listes dans le resume, sans annotation ni effet sur le verdict. Un appel
     * `parent::nom()` reste compte : il vise forcement la classe mere.
     *
     * @param  array<string, array{name: string, where: string}> $fresh    Unpublished API
     * @param  array<string, array{functions: array<string, string>, methods: list<array{name: string, class: string, public: bool}>, classes: array<string, string>, usages: list<array{kind: string, key: string, line: int, parent: bool}>, guards: array<string, true>}> $analyses Module files analyses
     * @return array<string, string>                                        Raison par symbole
     */
    private static function ambiguous(array $fresh, array $analyses): array
    {
        $internal = [];
        foreach (array_merge(get_declared_classes(), get_declared_interfaces()) as $class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->isInternal()) {
                foreach ($reflection->getMethods() as $method) {
                    $internal[strtolower($method->getName())] ??= $reflection->getName();
                }
            }
        }
        $own = [];
        foreach ($analyses as $file => $analysis) {
            foreach ($analysis['methods'] as $method) {
                $own[strtolower($method['name'])] ??= $method['class'] . ', ' . $file;
            }
        }

        $ambiguous = [];
        foreach (array_keys($fresh) as $id) {
            if (strncmp($id, 'method:', 7) !== 0) {
                continue;
            }
            $key = substr($id, 7);
            if (isset($internal[$key])) {
                $ambiguous[$id] = 'méthode de la classe PHP ' . $internal[$key];
            } elseif (isset($own[$key])) {
                $ambiguous[$id] = 'déclarée aussi par le module (' . $own[$key] . ')';
            }
        }
        return $ambiguous;
    }

    /**
     * Split the hits between symbols introduced by the pull request and symbols already on its base.
     *
     * A symbol is introduced when the PR raises its number of unguarded calls: a call that only moves
     * (a line rewritten, a block reindented) leaves the count unchanged and does not redden the check.
     *
     * @param  string                                                               $dir       Module git directory
     * @param  SaturnePublishedApiBlobReader                                        $reader    Module blob reader
     * @param  string                                                               $base      Base commit
     * @param  string                                                               $head      Head commit
     * @param  array<string, array<string, list<array{line: int, guarded: bool}>>> $headHits  Hits par fichier puis par symbole
     * @param  array<string, array{name: string, where: string}>                    $fresh     Unpublished API
     * @param  array<string, string>                                                $ambiguous Ambiguous method names
     * @return array{0: array<string, int>, 1: array<string, array<int, true>>}                Symboles introduits (appels ajoutes) et lignes ajoutees par fichier
     */
    private static function introducedSymbols(string $dir, SaturnePublishedApiBlobReader $reader, string $base, string $head, array $headHits, array $fresh, array $ambiguous): array
    {
        $changed = array_filter(
            self::lines(self::git($dir, ['diff', '--no-renames', '--name-only', '-z', $base, $head, '--', '*.php']), "\0"),
            fn (string $file): bool => !self::ignored($file, self::MODULE_IGNORED)
        );

        $delta = [];
        foreach ($changed as $file) {
            foreach ($headHits[$file] ?? [] as $id => $places) {
                $delta[$id] = ($delta[$id] ?? 0) + self::unguarded($places);
            }
            $code = $reader->read($base, $file);
            if ($code === null) {
                continue;
            }
            foreach (self::hits(self::analyse($code), $fresh, $ambiguous) as $id => $places) {
                $delta[$id] = ($delta[$id] ?? 0) - self::unguarded($places);
            }
        }
        $introduced = array_filter($delta, fn (int $count): bool => $count > 0);

        $added = [];
        $file  = null;
        foreach (self::lines(self::git($dir, ['diff', '--no-renames', '--no-color', '--unified=0', $base, $head, '--', '*.php']), "\n") as $line) {
            if (strncmp($line, '+++ ', 4) === 0) {
                $file = $line === '+++ /dev/null' ? null : substr($line, strlen('+++ b/'));
            } elseif ($file !== null && preg_match('/^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@/', $line, $m)) {
                $start = (int) $m[1];
                // « +12 » sans compte vaut une ligne ; « +12,0 » n'en ajoute aucune (suppression pure).
                $count = isset($m[2]) ? (int) $m[2] : 1;
                for ($i = $start; $i < $start + $count; $i++) {
                    $added[$file][$i] = true;
                }
            }
        }
        return [$introduced, $added];
    }

    /**
     * Build the Markdown report and queue the annotations.
     *
     * @param  string                                                               $module     Module name
     * @param  string                                                               $published  Published tag
     * @param  string                                                               $saturneRef Analysed Saturne commit
     * @param  array<string, array{name: string, where: string}>                    $fresh      Unpublished API
     * @param  array<string, string>                                                $ambiguous  Ambiguous method names
     * @param  array<string, array<string, list<array{line: int, guarded: bool}>>> $headHits   Hits par fichier puis par symbole
     * @param  string|null                                                          $base       Base commit, null hors PR
     * @param  array<string, int>                                                   $introduced Symboles introduits par la PR
     * @param  array<string, array<int, true>>                                      $addedLines Lignes ajoutees par fichier
     * @return string                                                                           Markdown
     */
    private function report(string $module, string $published, string $saturneRef, array $fresh, array $ambiguous, array $headHits, ?string $base, array $introduced, array $addedLines): string
    {
        $calls   = [];
        $guarded = [];
        foreach ($headHits as $file => $symbols) {
            foreach ($symbols as $id => $places) {
                foreach ($places as $place) {
                    if ($place['guarded']) {
                        $guarded[$id][] = ['file' => $file, 'line' => $place['line']];
                    } else {
                        $calls[$id][] = ['file' => $file, 'line' => $place['line']];
                    }
                }
            }
        }
        ksort($calls);
        ksort($guarded);
        $intro    = array_intersect_key($calls, $introduced);
        $existing = array_diff_key($calls, $introduced);

        $md   = [];
        $md[] = '## API Saturne publiée — ' . $module;
        $md[] = '';
        $md[] = 'Saturne `' . substr($saturneRef, 0, 8) . '` comparé à **' . $published . '**, la dernière version publiée : '
            . count($fresh) . ' symbole(s) n\'existent dans aucune version publiée.';
        $md[] = '';

        if ($base !== null && empty($intro)) {
            $md[] = 'Cette PR n\'introduit aucun appel à de l\'API non publiée (base `' . substr($base, 0, 8) . '`).';
            $md[] = '';
        } elseif ($base !== null) {
            $md[] = '### Introduits par cette PR';
            $md[] = '';
            $md[] = '**Cette PR appelle de l\'API absente de Saturne ' . $published . ' : publier Saturne avant la release du module.**';
            $md[] = '';
            $md   = array_merge($md, self::table($fresh, $intro));
            $md[] = '';
            foreach ($intro as $id => $places) {
                $onAdded = array_filter($places, fn (array $p): bool => isset($addedLines[$p['file']][$p['line']]));
                foreach (empty($onAdded) ? $places : $onAdded as $place) {
                    $this->annotate($place, 'API Saturne non publiée, introduite par cette PR', self::message($fresh, $id, $published, 1));
                }
            }
        }

        if (!empty($existing)) {
            $md[] = $base !== null ? '### Déjà sur la branche de base' : '### Appels présents sur la branche';
            $md[] = '';
            $md[] = 'Ils ne font pas échouer le contrôle, mais la release du module devra attendre celle de Saturne.';
            $md[] = '';
            $md   = array_merge($md, self::table($fresh, $existing));
            $md[] = '';
            // Une annotation par symbole, sur son premier appel : GitHub n'en affiche que dix par
            // etape, le resume porte la liste complete.
            foreach ($existing as $id => $places) {
                $this->annotate($places[0], 'API Saturne non publiée', self::message($fresh, $id, $published, count($places)));
            }
        } elseif (empty($intro)) {
            $md[] = 'Le module n\'appelle que de l\'API publiée dans Saturne ' . $published . '.';
            $md[] = '';
        }

        if (!empty($guarded)) {
            $md[] = '### Appels protégés';
            $md[] = '';
            $md[] = 'Le même fichier teste leur existence (`function_exists()`, `method_exists()`, `class_exists()`, `file_exists()`…) : sans effet sur le verdict.';
            $md[] = '';
            $md   = array_merge($md, self::table($fresh, $guarded));
            $md[] = '';
        }

        if (!empty($ambiguous)) {
            $md[] = '<details><summary>Méthodes non vérifiées, leur nom ne désigne pas forcément Saturne (' . count($ambiguous) . ')</summary>';
            $md[] = '';
            foreach ($ambiguous as $id => $reason) {
                $md[] = '- `' . $fresh[$id]['name'] . '` : ' . $reason;
            }
            $md[] = '';
            $md[] = '</details>';
            $md[] = '';
        }

        if (!empty($fresh)) {
            $md[] = '<details><summary>API ajoutée à Saturne depuis ' . $published . ' (' . count($fresh) . ')</summary>';
            $md[] = '';
            foreach ($fresh as $id => $def) {
                $md[] = '- ' . self::label($id) . ' `' . $def['name'] . '`' . ($def['where'] !== '' ? ' — ' . $def['where'] : '');
            }
            $md[] = '';
            $md[] = '</details>';
            $md[] = '';
        }

        return implode("\n", $md) . "\n";
    }

    /**
     * Markdown table of symbols and their call sites.
     *
     * @param  array<string, array{name: string, where: string}>   $fresh   Unpublished API
     * @param  array<string, list<array{file: string, line: int}>> $symbols Emplacements par symbole
     * @return list<string>                                                 Lignes Markdown
     */
    private static function table(array $fresh, array $symbols): array
    {
        $rows = ['| Symbole | Défini dans Saturne | Appels | Où |', '|---|---|---|---|'];
        foreach ($symbols as $id => $places) {
            $where = array_map(fn (array $p): string => '`' . $p['file'] . ':' . $p['line'] . '`', array_slice($places, 0, self::MAX_PLACES));
            if (count($places) > self::MAX_PLACES) {
                $where[] = '…';
            }
            $rows[] = '| ' . self::label($id) . ' `' . $fresh[$id]['name'] . '` | ' . ($fresh[$id]['where'] !== '' ? $fresh[$id]['where'] : '—') . ' | '
                . count($places) . ' | ' . implode(', ', $where) . ' |';
        }
        return $rows;
    }

    /**
     * Queue a warning annotation on a call site.
     *
     * @param  array{file: string, line: int} $place   Call site
     * @param  string                         $title   Annotation title
     * @param  string                         $message Annotation message
     * @return void
     */
    private function annotate(array $place, string $title, string $message): void
    {
        $data     = ['%' => '%25', "\r" => '%0D', "\n" => '%0A'];
        $property = $data + [':' => '%3A', ',' => '%2C'];
        $this->annotations[] = '::warning file=' . strtr($place['file'], $property) . ',line=' . $place['line'] . ',title=' . strtr($title, $property) . '::' . strtr($message, $data);
    }

    /**
     * Annotation message of a symbol.
     *
     * @param  array<string, array{name: string, where: string}> $fresh     Unpublished API
     * @param  string                                            $id        Symbol id
     * @param  string                                            $published Published tag
     * @param  int                                               $count     Number of call sites in the module
     * @return string                                                       Message
     */
    private static function message(array $fresh, string $id, string $published, int $count): string
    {
        $where = $fresh[$id]['where'] !== '' ? ', ' . $fresh[$id]['where'] : '';
        return $fresh[$id]['name'] . ' (' . self::label($id) . $where . ') n\'existe dans aucune version publiée de Saturne (dernière : ' . $published
            . ') : un client qui a ce Saturne planterait ici' . ($count > 1 ? ', et sur les ' . ($count - 1) . ' autre(s) appel(s) du résumé' : '')
            . '. Publier Saturne avant la release du module.';
    }

    /**
     * Label of the kind of a symbol id.
     *
     * @param  string $id Symbol id « kind:key »
     * @return string     fonction, méthode, classe or fichier
     */
    private static function label(string $id): string
    {
        $kind = substr($id, 0, (int) strpos($id, ':'));
        return self::KIND_LABELS[$kind] ?? $kind;
    }

    /**
     * Count the unguarded call sites.
     *
     * @param  list<array{line: int, guarded: bool}> $places Call sites
     * @return int                                           Count
     */
    private static function unguarded(array $places): int
    {
        return count(array_filter($places, fn (array $p): bool => !$p['guarded']));
    }

    /**
     * Keep the usages of a file that hit the unpublished API.
     *
     * @param  array{functions: array<string, string>, methods: list<array{name: string, class: string, public: bool}>, classes: array<string, string>, usages: list<array{kind: string, key: string, line: int, parent: bool}>, guards: array<string, true>} $analysis File analysis
     * @param  array<string, array{name: string, where: string}> $fresh     Unpublished API
     * @param  array<string, string>                             $ambiguous Ambiguous method names
     * @return array<string, list<array{line: int, guarded: bool}>>         Emplacements par symbole
     */
    private static function hits(array $analysis, array $fresh, array $ambiguous): array
    {
        $hits = [];
        foreach ($analysis['usages'] as $usage) {
            $id = $usage['kind'] . ':' . $usage['key'];
            if (isset($fresh[$id]) && (!isset($ambiguous[$id]) || $usage['parent'])) {
                $hits[$id][] = ['line' => $usage['line'], 'guarded' => isset($analysis['guards'][$usage['key']])];
            }
        }
        return $hits;
    }

    /**
     * Tokenize a PHP file: declared functions, methods and classes, and the symbols it uses.
     *
     * @param  string $code PHP source
     * @return array{functions: array<string, string>, methods: list<array{name: string, class: string, public: bool}>, classes: array<string, string>, usages: list<array{kind: string, key: string, line: int, parent: bool}>, guards: array<string, true>}
     */
    public static function analyse(string $code): array
    {
        $result = ['functions' => [], 'methods' => [], 'classes' => [], 'usages' => [], 'guards' => []];

        // Jetons significatifs seulement : sans espaces ni commentaires, « precedent » et « suivant »
        // se lisent directement. Les jetons d'un caractere n'ont pas de numero de ligne : on le
        // reporte depuis le jeton precedent.
        $tokens = [];
        $line   = 1;
        foreach (token_get_all($code) as $token) {
            if (is_array($token)) {
                $line = $token[2];
                if (!in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    $tokens[] = [$token[0], $token[1], $line];
                }
                $line += substr_count($token[1], "\n");
            } else {
                $tokens[] = [$token, $token, $line];
            }
        }

        $names   = [T_STRING, T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED];
        $objects = [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON];
        // Le & de « function &nom() » : un jeton nomme depuis PHP 8.1, un simple caractere avant.
        $amps    = ['&', T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG, T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG];
        $depth   = 0;
        $classes = [];
        $pending = null;
        $count   = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            [$id, $text, $line] = $tokens[$i];
            $prev = $i > 0 ? $tokens[$i - 1][0] : null;
            $next = $i + 1 < $count ? $tokens[$i + 1][0] : null;

            if ($id === '{' || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
                if ($pending !== null) {
                    $classes[] = ['name' => $pending, 'depth' => $depth];
                    $pending   = null;
                }
                $depth++;
            } elseif ($id === '}') {
                $depth--;
                if (!empty($classes) && $classes[count($classes) - 1]['depth'] === $depth) {
                    array_pop($classes);
                }
            } elseif (in_array($id, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true) && $prev !== T_DOUBLE_COLON) {
                if ($prev === T_NEW) {
                    // Classe anonyme : ses methodes ne sont pas de l'API.
                    $pending = '';
                } elseif ($next === T_STRING) {
                    $pending = $tokens[$i + 1][1];
                    $result['classes'][strtolower($pending)] = $pending;
                }
            } elseif ($id === T_FUNCTION) {
                $j = in_array($next, $amps, true) ? $i + 2 : $i + 1;
                if ($j < $count && $tokens[$j][0] === T_STRING) {
                    $name = $tokens[$j][1];
                    $top  = empty($classes) ? null : $classes[count($classes) - 1];
                    if ($top !== null && $top['depth'] === $depth - 1) {
                        if ($top['name'] !== '') {
                            $result['methods'][] = ['name' => $name, 'class' => $top['name'], 'public' => self::isPublic($tokens, $i)];
                        }
                    } else {
                        // Fonction globale, y compris declaree dans un if (!function_exists(...)).
                        $result['functions'][strtolower($name)] = $name;
                    }
                }
            } elseif ($id === T_NEW || $id === T_INSTANCEOF) {
                if ($next !== null && in_array($next, $names, true)) {
                    self::useClass($result, $tokens[$i + 1][1], $tokens[$i + 1][2]);
                }
            } elseif ($id === T_EXTENDS || $id === T_IMPLEMENTS) {
                for ($j = $i + 1; $j < $count && (in_array($tokens[$j][0], $names, true) || $tokens[$j][0] === ','); $j++) {
                    if ($tokens[$j][0] !== ',') {
                        self::useClass($result, $tokens[$j][1], $tokens[$j][2]);
                    }
                }
            } elseif (in_array($id, $names, true)) {
                if ($next === T_DOUBLE_COLON && !in_array(strtolower($text), ['self', 'parent', 'static'], true)) {
                    self::useClass($result, $text, $line);
                } elseif ($next === '(') {
                    if ($prev !== null && in_array($prev, $objects, true)) {
                        $parent = $prev === T_DOUBLE_COLON && $i > 1 && strtolower($tokens[$i - 2][1]) === 'parent';
                        $result['usages'][] = ['kind' => 'method', 'key' => strtolower($text), 'line' => $line, 'parent' => $parent];
                    } elseif ($prev !== T_FUNCTION && $prev !== T_NEW && !(in_array($prev, $amps, true) && $i > 1 && $tokens[$i - 2][0] === T_FUNCTION)) {
                        $function = strtolower(ltrim($text, '\\'));
                        $result['usages'][] = ['kind' => 'function', 'key' => $function, 'line' => $line, 'parent' => false];
                        if (in_array($function, self::GUARDS, true)) {
                            self::collectGuards($result, $tokens, $i + 1);
                        }
                    }
                }
            } elseif ($id === T_CONSTANT_ENCAPSED_STRING && preg_match_all(self::FILE_PATTERN, $text, $m)) {
                foreach ($m[1] as $path) {
                    $result['usages'][] = ['kind' => 'file', 'key' => $path, 'line' => $line, 'parent' => false];
                }
            }
        }
        return $result;
    }

    /**
     * Whether the method declared at $i has no private or protected modifier.
     *
     * @param  list<array{0: int|string, 1: string, 2: int}> $tokens Significant tokens
     * @param  int                                           $i      Index of the T_FUNCTION token
     * @return bool                                                  True when public
     */
    private static function isPublic(array $tokens, int $i): bool
    {
        $modifiers = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL];
        for ($j = $i - 1; $j >= 0 && in_array($tokens[$j][0], $modifiers, true); $j--) {
            if ($tokens[$j][0] === T_PRIVATE || $tokens[$j][0] === T_PROTECTED) {
                return false;
            }
        }
        return true;
    }

    /**
     * Record a class usage.
     *
     * @param  array{functions: array<string, string>, methods: list<array{name: string, class: string, public: bool}>, classes: array<string, string>, usages: list<array{kind: string, key: string, line: int, parent: bool}>, guards: array<string, true>} $result Analysis in progress
     * @param  string $name Class name as written
     * @param  int    $line Line
     * @return void
     */
    private static function useClass(array &$result, string $name, int $line): void
    {
        $result['usages'][] = ['kind' => 'class', 'key' => strtolower(ltrim($name, '\\')), 'line' => $line, 'parent' => false];
    }

    /**
     * Record the string literals passed to a guard such as function_exists('x') or method_exists($o, 'x').
     *
     * La garde vaut pour tout le fichier : sa position par rapport a l'appel n'est pas verifiee.
     *
     * @param  array{functions: array<string, string>, methods: list<array{name: string, class: string, public: bool}>, classes: array<string, string>, usages: list<array{kind: string, key: string, line: int, parent: bool}>, guards: array<string, true>} $result Analysis in progress
     * @param  list<array{0: int|string, 1: string, 2: int}> $tokens Significant tokens
     * @param  int                                           $open   Index of the opening parenthesis
     * @return void
     */
    private static function collectGuards(array &$result, array $tokens, int $open): void
    {
        $level = 0;
        $count = count($tokens);
        for ($j = $open; $j < $count; $j++) {
            $id = $tokens[$j][0];
            if ($id === '(') {
                $level++;
            } elseif ($id === ')') {
                if (--$level === 0) {
                    return;
                }
            } elseif ($id === T_CONSTANT_ENCAPSED_STRING) {
                $value = trim($tokens[$j][1], '\'"');
                if (preg_match_all(self::FILE_PATTERN, $value, $m)) {
                    foreach ($m[1] as $path) {
                        $result['guards'][$path] = true;
                    }
                    continue;
                }
                $value = strtolower($value);
                $short = strrpos($value, '::');
                $result['guards'][$short === false ? ltrim($value, '\\') : substr($value, $short + 2)] = true;
            }
        }
    }

    /**
     * PHP files of a tree, minus the ignored directories.
     *
     * @param  string       $dir     Git directory
     * @param  string       $ref     Commit
     * @param  list<string> $ignored Ignored directory prefixes
     * @return list<string>          Paths
     */
    private static function phpFiles(string $dir, string $ref, array $ignored): array
    {
        $files = self::lines(self::git($dir, ['ls-tree', '-r', '-z', '--name-only', $ref]), "\0");
        return array_values(array_filter($files, fn (string $f): bool => substr($f, -4) === '.php' && !self::ignored($f, $ignored)));
    }

    /**
     * Whether a path sits under one of the ignored prefixes.
     *
     * @param  string       $path    Path
     * @param  list<string> $ignored Ignored directory prefixes
     * @return bool                  True when ignored
     */
    private static function ignored(string $path, array $ignored): bool
    {
        foreach ($ignored as $prefix) {
            if (strncmp($path, $prefix, strlen($prefix)) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Highest X.Y.Z tag of a repository.
     *
     * @param  string $dir Git directory
     * @return string      Tag
     */
    private static function latestTag(string $dir): string
    {
        $tags = array_values(array_filter(self::lines(self::git($dir, ['tag', '--list']), "\n"), fn (string $t): bool => (bool) preg_match('/^\d+\.\d+\.\d+$/', $t)));
        if (empty($tags)) {
            throw new RuntimeException('aucun tag X.Y.Z dans ' . $dir . ' : passer --published, ou recuperer les tags');
        }
        usort($tags, 'version_compare');
        return $tags[count($tags) - 1];
    }

    /**
     * Resolve a revision to a commit sha.
     *
     * @param  string $dir Git directory
     * @param  string $rev Revision
     * @return string      Sha
     */
    private static function revParse(string $dir, string $rev): string
    {
        return trim(self::git($dir, ['rev-parse', '--verify', '--quiet', $rev . '^{commit}']));
    }

    /**
     * Split a command output.
     *
     * @param  string       $output    Output
     * @param  string       $separator Separator
     * @return list<string>            Non-empty lines
     */
    private static function lines(string $output, string $separator): array
    {
        return array_values(array_filter(explode($separator, $output), fn (string $l): bool => $l !== ''));
    }

    /**
     * Run git and return its standard output.
     *
     * @param  string       $dir  Git directory
     * @param  list<string> $args Arguments
     * @return string             Standard output
     */
    private static function git(string $dir, array $args): string
    {
        $process = proc_open(array_merge(['git', '-C', $dir, '-c', 'core.quotePath=false'], $args), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('git introuvable');
        }
        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0) {
            throw new RuntimeException('git ' . implode(' ', $args) . ' a echoue dans ' . $dir . ' : ' . trim($err));
        }
        return $out;
    }
}

/**
 * Lecture de blobs par un seul `git cat-file --batch` : un processus pour tout l'arbre, au lieu d'un
 * `git show` par fichier.
 */
final class SaturnePublishedApiBlobReader
{
    /**
     * @var resource Process git cat-file
     */
    private $process;

    /**
     * @var array<int, resource> Pipes du processus
     */
    private array $pipes;

    /**
     * Constructor.
     *
     * @param string $dir Git directory
     */
    public function __construct(string $dir)
    {
        $process = proc_open(['git', '-C', $dir, 'cat-file', '--batch'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('git cat-file introuvable');
        }
        $this->process = $process;
        $this->pipes   = $pipes;
    }

    /**
     * Content of a file at a commit, null when it does not exist there.
     *
     * @param  string      $ref  Commit
     * @param  string      $path Path
     * @return string|null       Content
     */
    public function read(string $ref, string $path): ?string
    {
        fwrite($this->pipes[0], $ref . ':' . $path . "\n");
        fflush($this->pipes[0]);
        $header = fgets($this->pipes[1]);
        if ($header === false) {
            throw new RuntimeException('git cat-file a cesse de repondre sur ' . $ref . ':' . $path);
        }
        // « <sha> blob <taille> », ou « <objet> missing » quand le fichier n'existe pas a ce commit.
        $parts = explode(' ', trim($header));
        if (count($parts) !== 3) {
            return null;
        }
        $size = (int) $parts[2];
        $data = '';
        while (strlen($data) < $size) {
            $chunk = fread($this->pipes[1], $size - strlen($data));
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('lecture tronquee de ' . $ref . ':' . $path);
            }
            $data .= $chunk;
        }
        // Chaque contenu est suivi d'un saut de ligne : s'il manque, la lecture suivante serait decalee.
        if (fread($this->pipes[1], 1) !== "\n") {
            throw new RuntimeException('git cat-file desynchronise apres ' . $ref . ':' . $path);
        }
        return $parts[1] === 'blob' ? $data : null;
    }

    /**
     * Stop the process.
     *
     * @return void
     */
    public function close(): void
    {
        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);
        proc_close($this->process);
    }
}

// Ne s'execute qu'en ligne de commande, pas quand un test inclut le fichier pour appeler analyse().
$arguments = PHP_SAPI === 'cli' && is_array($_SERVER['argv'] ?? null) ? array_map('strval', $_SERVER['argv']) : [];
if (isset($arguments[0]) && realpath($arguments[0]) === __FILE__) {
    $options = [];
    foreach (array_slice($arguments, 1) as $arg) {
        if (preg_match('/^--([a-z-]+)=(.*)$/s', $arg, $m)) {
            $options[$m[1]] = $m[2];
        } else {
            fwrite(STDERR, 'argument inattendu : ' . $arg . "\n");
            exit(2);
        }
    }
    try {
        exit((new SaturnePublishedApiCheck($options))->run());
    } catch (Throwable $e) {
        echo '::error title=API Saturne publiée::' . $e->getMessage() . "\n";
        exit(2);
    }
}
