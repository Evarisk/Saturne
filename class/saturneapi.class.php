<?php

/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    class/saturneapi.class.php
 * \ingroup saturne
 * \brief   Generic multi-entity REST API dispatcher for SaturneObject CRUD,
 *          lifecycle and line endpoints.
 */

require_once DOL_DOCUMENT_ROOT . '/api/class/api.class.php';

use Luracast\Restler\RestException;

/**
 * Abstract multi-entity REST API base.
 *
 * Dolibarr's REST router resolves an URL like /xxx by computing a file path
 * "<xxx>/class/api_<xxx>.class.php" — which means a custom module can only
 * expose ONE root endpoint matching its module directory. To still serve
 * many objects under that single endpoint, this base provides a dispatcher
 * keyed by an entity slug, plus protected do*() helpers that the concrete
 * subclass wires to namespaced @url routes (e.g. "controls/{id}",
 * "surveys/{id}/lines").
 *
 * The file is intentionally NOT prefixed with "api_" so Dolibarr's
 * auto-discovery scan does not try to expose the abstract class itself.
 */
abstract class SaturneApi extends DolibarrApi
{
    /**
     * Lowercase Dolibarr module name owning the rights tree.
     *
     * Used to resolve checkPermission() against
     * $user->rights->{$module}->...
     *
     * @var string
     */
    protected string $module = '';

    /**
     * Per-entity configuration.
     *
     * Indexed by entity slug. Recognised keys per entity:
     *   - 'class'          (string, required) FQN of the SaturneObject subclass.
     *   - 'fields'         (string[])         required fields for POST.
     *   - 'permissions'    (array<string,string>) action => right path under $module.
     *   - 'lineClass'      (string)           FQN of the line object class (optional).
     *   - 'lineForeignKey' (string)           FK column on the line table pointing to the parent.
     *   - 'lineFields'     (string[])         required fields for postLine.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $entities = [];

    /**
     * Object instance cache to avoid re-instantiating between calls.
     *
     * @var array<string, CommonObject>
     */
    private array $objectCache = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    // -----------------------------------------------------------------
    // CRUD dispatchers
    // -----------------------------------------------------------------

    /**
     * Fetch one object by id.
     *
     * @param string $entityKey Entity slug declared in $entities
     * @param int    $id        Object id
     *
     * @return object Cleaned object
     *
     * @throws RestException 403, 404
     */
    protected function _get(string $entityKey, $id)
    {
        $this->_checkPermission($entityKey, 'read');

        $object = $this->_getObject($entityKey);
        if ($object->fetch((int) $id) <= 0) {
            throw new RestException(404, $entityKey . ' not found');
        }

        return $this->_cleanObjectDatas($object);
    }

    /**
     * List objects.
     *
     * @param string $entityKey  Entity slug
     * @param string $sortfield  Sort field
     * @param string $sortorder  ASC|DESC
     * @param int    $limit      Page size (0 = no limit)
     * @param int    $page       Page index, starting at 0
     * @param string $sqlfilters Universal Search filter
     *
     * @return array<int, object>
     *
     * @throws RestException 400, 403, 503
     */
    protected function _index(string $entityKey, $sortfield = 't.rowid', $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        $this->_checkPermission($entityKey, 'read');

        $object = $this->_getObject($entityKey);
        $offset = $limit > 0 ? $limit * max(0, (int) $page) : 0;
        $filter = [];

        if (!empty($sqlfilters)) {
            $errorMessage = '';
            $customSql    = forgeSQLFromUniversalSearchCriteria($sqlfilters, $errorMessage, 1);
            if ($errorMessage !== '') {
                throw new RestException(400, 'Error when validating parameter sqlfilters -> ' . $errorMessage);
            }
            $filter['customsql'] = $customSql;
        }

        $records = $object->fetchAll($sortorder, $sortfield, (int) $limit, (int) $offset, $filter);
        if (!is_array($records)) {
            throw new RestException(503, 'Error when retrieving list: ' . implode(',', (array) $object->errors));
        }

        $cleaned = [];
        foreach ($records as $record) {
            $cleaned[] = $this->_cleanObjectDatas($record);
        }
        return $cleaned;
    }

    /**
     * Create one object.
     *
     * @param string                   $entityKey    Entity slug
     * @param array<string,mixed>|null $request_data Object data
     *
     * @return int New object id
     *
     * @throws RestException 400, 403, 500
     */
    protected function _post(string $entityKey, $request_data = null)
    {
        $this->_checkPermission($entityKey, $this->_resolveCreateAction($entityKey));
        $this->_validate($entityKey, $request_data);

        $object = $this->_getObject($entityKey);
        $this->_assignRequestData($object, (array) $request_data);

        if ($object->create(DolibarrApiAccess::$user) <= 0) {
            throw new RestException(500, 'Error creating ' . $entityKey, $this->_collectErrors($object));
        }

        return (int) $object->id;
    }

    /**
     * Update one object.
     *
     * @param string                   $entityKey    Entity slug
     * @param int                      $id           Object id
     * @param array<string,mixed>|null $request_data Fields to update
     *
     * @return object Cleaned, freshly-fetched object
     *
     * @throws RestException 403, 404, 500
     */
    protected function _put(string $entityKey, $id, $request_data = null)
    {
        $this->_checkPermission($entityKey, 'write');

        $object = $this->_getObject($entityKey);
        if ($object->fetch((int) $id) <= 0) {
            throw new RestException(404, $entityKey . ' not found');
        }

        $this->_assignRequestData($object, (array) $request_data);

        if ($object->update(DolibarrApiAccess::$user) <= 0) {
            throw new RestException(500, 'Error updating ' . $entityKey, $this->_collectErrors($object));
        }

        $object->fetch((int) $id);
        return $this->_cleanObjectDatas($object);
    }

    /**
     * Delete one object.
     *
     * @param string $entityKey Entity slug
     * @param int    $id        Object id
     *
     * @return array<string, array<string, int|string>>
     *
     * @throws RestException 403, 404, 500
     */
    protected function _delete(string $entityKey, $id)
    {
        $this->_checkPermission($entityKey, 'delete');

        $object = $this->_getObject($entityKey);
        if ($object->fetch((int) $id) <= 0) {
            throw new RestException(404, $entityKey . ' not found');
        }

        if ($object->delete(DolibarrApiAccess::$user) <= 0) {
            throw new RestException(500, 'Error deleting ' . $entityKey, $this->_collectErrors($object));
        }

        return [
            'success' => [
                'code'    => 200,
                'message' => ucfirst($entityKey) . ' deleted',
            ],
        ];
    }

    // -----------------------------------------------------------------
    // Lifecycle dispatchers
    // -----------------------------------------------------------------

    /**
     * Run a SaturneObject status transition.
     *
     * @param string $entityKey  Entity slug
     * @param int    $id         Object id
     * @param string $methodName Method to invoke on the object
     *
     * @return object
     *
     * @throws RestException
     */
    protected function _setStatus(string $entityKey, $id, string $methodName)
    {
        $this->_checkPermission($entityKey, 'write');

        $object = $this->_getObject($entityKey);
        if ($object->fetch((int) $id) <= 0) {
            throw new RestException(404, $entityKey . ' not found');
        }
        if (!method_exists($object, $methodName)) {
            throw new RestException(501, 'Method ' . $methodName . ' not supported on ' . $entityKey);
        }

        $result = $object->{$methodName}(DolibarrApiAccess::$user);
        if ($result === 0) {
            throw new RestException(304, 'Nothing done — object may already be in target status');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error during ' . $methodName, $this->_collectErrors($object));
        }

        $object->fetch((int) $id);
        return $this->_cleanObjectDatas($object);
    }

    // -----------------------------------------------------------------
    // Lines dispatchers
    // -----------------------------------------------------------------

    /**
     * Get lines of an object.
     *
     * @param string $entityKey Entity slug (must define lineClass + lineForeignKey)
     * @param int    $id        Parent object id
     *
     * @return array<int, object>
     */
    protected function _getLines(string $entityKey, $id)
    {
        $this->_checkPermission($entityKey, 'read');
        [$lineClass, $foreignKey] = $this->_resolveLineConfig($entityKey);

        $object = $this->_getObject($entityKey);
        if ($object->fetch((int) $id) <= 0) {
            throw new RestException(404, $entityKey . ' not found');
        }

        $line = new $lineClass($this->db);
        $rows = $line->fetchAll('', '', 0, 0, [$foreignKey => (int) $id]);
        if (!is_array($rows)) {
            throw new RestException(503, 'Error when retrieving lines: ' . implode(',', (array) $line->errors));
        }

        $cleaned = [];
        foreach ($rows as $row) {
            $cleaned[] = $this->_cleanObjectDatas($row);
        }
        return $cleaned;
    }

    /**
     * Add a line to an object.
     *
     * @param string                   $entityKey    Entity slug
     * @param int                      $id           Parent id
     * @param array<string,mixed>|null $request_data Line fields
     *
     * @return int New line id
     */
    protected function _postLine(string $entityKey, $id, $request_data = null)
    {
        $this->_checkPermission($entityKey, 'write');
        [$lineClass, $foreignKey, $lineFields] = $this->_resolveLineConfig($entityKey, true);

        $object = $this->_getObject($entityKey);
        if ($object->fetch((int) $id) <= 0) {
            throw new RestException(404, $entityKey . ' not found');
        }

        $payload = (array) $request_data;
        foreach ($lineFields as $field) {
            if (!isset($payload[$field])) {
                throw new RestException(400, '"' . $field . '" line field missing');
            }
        }

        $line = new $lineClass($this->db);
        $line->{$foreignKey} = (int) $id;
        $this->_assignRequestData($line, $payload);

        if ($line->create(DolibarrApiAccess::$user) <= 0) {
            throw new RestException(500, 'Error creating line', $this->_collectErrors($line));
        }

        return (int) $line->id;
    }

    /**
     * Update a line of an object.
     *
     * @param string                   $entityKey    Entity slug
     * @param int                      $id           Parent id
     * @param int                      $lineid       Line id
     * @param array<string,mixed>|null $request_data Fields to update
     *
     * @return object Cleaned line
     */
    protected function _putLine(string $entityKey, $id, $lineid, $request_data = null)
    {
        $this->_checkPermission($entityKey, 'write');
        [$lineClass, $foreignKey] = $this->_resolveLineConfig($entityKey);

        $object = $this->_getObject($entityKey);
        if ($object->fetch((int) $id) <= 0) {
            throw new RestException(404, $entityKey . ' not found');
        }

        $line = new $lineClass($this->db);
        if ($line->fetch((int) $lineid) <= 0) {
            throw new RestException(404, 'Line not found');
        }
        if ((int) $line->{$foreignKey} !== (int) $id) {
            throw new RestException(404, 'Line does not belong to this ' . $entityKey);
        }

        $this->_assignRequestData($line, (array) $request_data);

        if ($line->update(DolibarrApiAccess::$user) <= 0) {
            throw new RestException(500, 'Error updating line', $this->_collectErrors($line));
        }

        $line->fetch((int) $lineid);
        return $this->_cleanObjectDatas($line);
    }

    /**
     * Delete a line.
     *
     * @param string $entityKey Entity slug
     * @param int    $id        Parent id
     * @param int    $lineid    Line id
     *
     * @return array<string, array<string, int|string>>
     */
    protected function _deleteLine(string $entityKey, $id, $lineid)
    {
        $this->_checkPermission($entityKey, 'delete');
        [$lineClass, $foreignKey] = $this->_resolveLineConfig($entityKey);

        $object = $this->_getObject($entityKey);
        if ($object->fetch((int) $id) <= 0) {
            throw new RestException(404, $entityKey . ' not found');
        }

        $line = new $lineClass($this->db);
        if ($line->fetch((int) $lineid) <= 0) {
            throw new RestException(404, 'Line not found');
        }
        if ((int) $line->{$foreignKey} !== (int) $id) {
            throw new RestException(404, 'Line does not belong to this ' . $entityKey);
        }

        if ($line->delete(DolibarrApiAccess::$user) <= 0) {
            throw new RestException(500, 'Error deleting line', $this->_collectErrors($line));
        }

        return [
            'success' => [
                'code'    => 200,
                'message' => 'Line deleted',
            ],
        ];
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Lazy-instantiate (and cache) the SaturneObject for a given entity.
     *
     * @param string $entityKey Entity slug
     *
     * @return CommonObject
     *
     * @throws RestException 500 Misconfigured
     */
    protected function _getObject(string $entityKey)
    {
        if (isset($this->objectCache[$entityKey])) {
            return $this->objectCache[$entityKey];
        }
        $config = $this->_getEntityConfig($entityKey);
        if (empty($config['class']) || !class_exists($config['class'])) {
            throw new RestException(500, 'Entity "' . $entityKey . '" has no valid "class" configured');
        }
        $class = $config['class'];
        $this->objectCache[$entityKey] = new $class($this->db);
        return $this->objectCache[$entityKey];
    }

    /**
     * Get the configuration array for an entity slug.
     *
     * @param string $entityKey Entity slug
     *
     * @return array<string, mixed>
     *
     * @throws RestException 404 Unknown entity
     */
    protected function _getEntityConfig(string $entityKey): array
    {
        if (!isset($this->entities[$entityKey])) {
            throw new RestException(404, 'Unknown entity "' . $entityKey . '"');
        }
        return $this->entities[$entityKey];
    }

    /**
     * Verify the API user holds the right associated with the given action
     * for the given entity.
     *
     * @param string $entityKey Entity slug
     * @param string $action    Action key in the entity's permissions map
     *
     * @throws RestException 403
     */
    protected function _checkPermission(string $entityKey, string $action): void
    {
        $config    = $this->_getEntityConfig($entityKey);
        $rightPath = $config['permissions'][$action] ?? null;
        if ($rightPath === null) {
            throw new RestException(403, 'No permission mapping for action "' . $action . '" on ' . $entityKey);
        }

        $segments = explode('->', $rightPath);
        $node     = DolibarrApiAccess::$user->rights->{$this->module} ?? null;
        foreach ($segments as $segment) {
            if (!is_object($node) || !isset($node->{$segment})) {
                throw new RestException(403, 'Missing right ' . $this->module . '->' . $rightPath);
            }
            $node = $node->{$segment};
        }

        if (empty($node)) {
            throw new RestException(403, 'Missing right ' . $this->module . '->' . $rightPath);
        }
    }

    /**
     * Pick the right key used for object creation. If the entity declares
     * a 'create' permission, use it; otherwise fall back to 'write'.
     *
     * @param string $entityKey Entity slug
     *
     * @return string Right key suitable for checkPermission()
     */
    protected function _resolveCreateAction(string $entityKey): string
    {
        $config = $this->_getEntityConfig($entityKey);
        if (isset($config['permissions']['create'])) {
            return 'create';
        }
        return 'write';
    }

    /**
     * Verify the entity is line-capable and return its line config triple.
     *
     * @param string $entityKey   Entity slug
     * @param bool   $needsFields When true, the third element is the line
     *                            mandatory-fields list (defaulting to []).
     *
     * @return array{0: class-string, 1: string, 2?: string[]}
     *
     * @throws RestException 500
     */
    protected function _resolveLineConfig(string $entityKey, bool $needsFields = false): array
    {
        $config = $this->_getEntityConfig($entityKey);
        $class  = $config['lineClass'] ?? '';
        $fk     = $config['lineForeignKey'] ?? '';
        if ($class === '' || !class_exists($class)) {
            throw new RestException(500, 'Entity "' . $entityKey . '" has no valid "lineClass" configured');
        }
        if ($fk === '') {
            throw new RestException(500, 'Entity "' . $entityKey . '" has no "lineForeignKey" configured');
        }
        if ($needsFields) {
            return [$class, $fk, (array) ($config['lineFields'] ?? [])];
        }
        return [$class, $fk];
    }

    /**
     * Validate the presence of mandatory fields declared in the entity
     * config for create.
     *
     * @param string                   $entityKey Entity slug
     * @param array<string,mixed>|null $data      Incoming request data
     *
     * @return array<string,mixed> Filtered mandatory fields
     *
     * @throws RestException 400
     */
    protected function _validate(string $entityKey, $data)
    {
        $config = $this->_getEntityConfig($entityKey);
        $fields = (array) ($config['fields'] ?? []);

        $data = (array) $data;
        $out  = [];
        foreach ($fields as $field) {
            if (!isset($data[$field])) {
                throw new RestException(400, '"' . $field . '" field missing');
            }
            $out[$field] = $data[$field];
        }
        return $out;
    }

    /**
     * Copy fields from a request payload onto an object, skipping the
     * id / caller magic keys and routing extrafields through
     * array_options.
     *
     * @param object              $target  Object to fill
     * @param array<string,mixed> $payload Request payload
     */
    protected function _assignRequestData($target, array $payload): void
    {
        foreach ($payload as $field => $value) {
            if ($field === 'id') {
                continue;
            }
            if ($field === 'caller') {
                $target->context['caller'] = sanitizeVal($value, 'aZ09');
                continue;
            }
            if ($field === 'array_options' && is_array($value)) {
                foreach ($value as $key => $val) {
                    $target->array_options[$key] = $this->_checkValForAPI($field, $val, $target);
                }
                continue;
            }
            $target->$field = $this->_checkValForAPI($field, $value, $target);
        }
    }

    /**
     * Aggregate $object->error and $object->errors into a single array
     * suitable for the third RestException argument.
     *
     * @param object $object Object exposing error / errors properties
     *
     * @return array<int, string>
     */
    protected function _collectErrors($object): array
    {
        $errors = [];
        if (!empty($object->error)) {
            $errors[] = (string) $object->error;
        }
        if (!empty($object->errors) && is_array($object->errors)) {
            foreach ($object->errors as $error) {
                $errors[] = (string) $error;
            }
        }
        return $errors;
    }

    /**
     * Strip internal SaturneObject properties before serialisation.
     *
     * @param object $object Object to clean
     *
     * @return object
     */
    protected function _cleanObjectDatas($object)
    {
        $object = parent::_cleanObjectDatas($object);

        unset($object->isCategoryManaged);
        unset($object->picto);
        unset($object->labelStatus);
        unset($object->labelStatusShort);
        unset($object->fields_to_get_for_pdf);
        unset($object->module);
        unset($object->element);
        unset($object->table_element);
        unset($object->table_element_line);
        unset($object->fk_element);
        unset($object->ismodifiable);

        return $object;
    }
}
