<?php

/**
 * Abstract Entity object
 *
 * This abstract class implements the EntityInterface with the intention that most
 * entity/model sub-classes will just extent this class to get 95% of their implementation.
 *
 * @package \Spot\Entity
 * @author Brandon lamb <brandon@brandonlamb.com>
 */

namespace Spot\Entity;

use Serializable, ArrayAccess;

abstract class AbstractEntity implements Serializable, ArrayAccess, EntityInterface
{
    /**
     * @var array, Entity data storage
     */
    protected $data = [];

    /**
     * @var array, Entity modified data storage
     */
    protected $dataModified = [];

    /**
     * @var array, ignored getter properties. Add a field/column here to not
     * attempt calling its getter method. For example, given an entity with a
     * "name" property and a "getName()" method, where you do *not want to call
     * "getName()" when accessing entity->name, add "name" to this array
     */
    protected $getterIgnore = [];

    /**
     * @var array, ignored setter properties. Add a field/column here to not
     * attempt calling its setter method. For example, given an entity with a
     * "name" property and a "setName()" method, where you do *not want to call
     * "setName()" when performing a entity->name = 'value' operation,
     * add "name" to this array
     */
    protected $setterIgnore = [];

    /**
     * @var array, Entity error messages (may be present after save attempt)
     */
    protected $errors = [];

    /**
     * Constructor. Allows setting object properties with array on construct
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->initFields();
        $data && $this->data($data, false);
    }

    /**
     * {@inheritDoc}
     */
    public function __isset($offset)
    {
        return $this->has($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function __get($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function __set($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return get_called_class();
    }

    /**
     * {@inheritDoc}
     */
    public function serialize()
    {
        return serialize($this->getData());
    }

    /**
     * {@inherit}
     */
    public function unserialize($serialized)
    {
        $this->setData(unserialize($serialized));
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($field, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
        unset($this->dataModified[$offset]);
    }

    /**
     * Check if model contains property
     * @param string $offset
     * @return bool
     */
    public function has($offset)
    {
        return isset($this->data[$offset]) || isset($this->dataModified[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function get($offset, $default = null)
    {
        // Check for custom getter method (override)
        $getMethod = 'get' . $offset;

        // We can't use isset for dataModified because it returns false for NULL values
        if (array_key_exists($offset, $this->dataModified)) {
            return $this->dataModified[$offset];
        }

        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }

        if (method_exists($this, $getMethod) && !array_key_exists($offset, $this->getterIgnore)) {
            // Tell this function to ignore the overload on further calls for this variable
            $this->getterIgnore[$offset] = 1;

            // Call custom getter
            $default = $this->$getMethod();

            // Remove ignore rule
            unset($this->getterIgnore[$offset]);
        }

        return $default;
    }

    /**
     * {@inheritDoc}
     * @todo - figure out how to remove dependency on Config static method
     */
    public function set($offset, $value)
    {
        // Check for custom setter method (override)
        $setMethod = 'set' . $field;

        $fields = $this->fields();

        // Run value through a filter call if set
        if (isset($fields[$field]['filter'])) {
            $value = call_user_func($fields[$field]['filter'], $value);
        } else if (method_exists($this, $setMethod) && !array_key_exists($field, $this->setterIgnore)) {
            // Tell this function to ignore the overload on further calls for this variable
            $this->setterIgnore[$field] = 1;

            // Call custom setter
            $value = $this->$setMethod($value);

            // Remove ignore rule
            unset($this->setterIgnore[$field]);
        } else if (isset($fields[$field])) {
            // Ensure value is set with type handler
            $typeHandler = Config::getTypeHandler($fields[$field]['type']);
            $value = $typeHandler::set($this, $value);
        }

        // Set the data value
        $this->dataModified[$field] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     * @todo - fix dependency on static Config
     */
    public function setData(array $data, $modified = true)
    {
        if (!is_object($data) && !is_array($data)) {
            throw new \InvalidArgumentException(__METHOD__ . " Expected array or object, " . gettype($data) . " given");
        }

        $fields = $this->fields();
        foreach ($data as $k => $v) {
            // Ensure value is set with type handler if Entity field type
            if (array_key_exists($k, $fields)) {
                $typeHandler = Config::getTypeHandler($fields[$k]['type']);
                $v = $typeHandler::set($this, $v);
            }

            if (true === $modified) {
                $this->dataModified[$k] = $v;
            } else {
                $this->data[$k] = $v;
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getData()
    {
        return array_merge($this->getUnmodified(), $this->getModified());
    }

    /**
     * {@inheritDoc}
     */
    public function getModified($field = null)
    {
        if (null !== $field) {
            return isset($this->dataModified[$field]) ? $this->dataModified[$field] : null;
        }
        return $this->dataModified;
    }

    /**
     * {@inheritDoc}
     */
    public function getUnmodified($field = null)
    {
        if (null !== $field) {
            return isset($this->data[$field]) ? $this->data[$field] : null;
        }
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function isEntityModified()
    {
       return (bool) !!count($this->getModified());
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     */
    public function isFieldModified($offset = null)
    {
        if (array_key_exists($offset, $this->getModified())) {
            if (null === $this->getModified($offset) || null === $this->getUnmodified($offset)) {
                // Use strict comparison for null values, non-strict otherwise
                return $this->getModified($offset) !== $this->getUnmodified($offset);
            }
            return $this->getModified($offset) != $this->getUnmodified($offset);
        } else if (array_key_exists($offset, $this->getUnmodified())) {
            return false;
        }

        throw new \InvalidArgumentExceptio("$offset is not a valid entity property");
    }

    /**
     * {@inheritDoc}
     */
    public function getTable()
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getSequence()
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getHooks()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getRelations()
    {
        return [];
    }








    /**
     * Set all field values to their defualts or null
     * @return $this
     */
    protected function initFields()
    {
        $fields = $this->getMetaData();
        foreach ($fields as $field => $opts) {
            if (!isset($this->data[$field])) {
                $this->data[$field] = isset($opts['default']) ? $opts['default'] : null;
            }
        }
        return $this;
    }








    /**
     * Return array of field data with data from the field names listed removed.
     * Essentially if your entity has fields id, first_name, last_name and you call
     * this method passing array('first_name'), you will be returned an array WITHOUT
     * the first_name field returned.
     * @param array $except, List of field names to exclude in data list returned
     * @return array
     */
    public function dataExcept(array $except)
    {
        return array_diff_key($this->data(), array_flip($except));
    }







    /**
     * Check if any errors exist
     * @param string $field OPTIONAL field name
     * @return bool
     */
/*
    public function hasErrors($field = null)
    {
        if (null !== $field) {
            return isset($this->errors[$field]) ? count($this->errors[$field]) > 0 : false;
        }
        return count($this->errors) > 0;
    }
*/
    /**
     * Error message getter/setter
     * @param $field string|array String return errors with field key, array sets errors
     * @return self|array|boolean Setter return self, getter returns array or boolean if key given and not found
     */
/*
    public function errors($msgs = null)
    {
        // Return errors for given field
        if (is_string($msgs)) {
            return isset($this->errors[$msgs]) ? $this->errors[$msgs] : [];
        } else if (is_array($msgs)) {
            // Set error messages from given array
            $this->errors = $msgs;
        }

        return $this->errors;
    }
*/
    /**
     * Add an error to error messages array
     * @param string $field Field name that error message relates to
     * @param mixed $msg Error message text - String or array of messages
     * @return $this
     */
/*
    public function error($field, $msg)
    {
        if (is_array($msg)) {
            // Add array of error messages about field
            foreach ($msg as $msgx) {
                $this->errors[$field][] = $msgx;
            }
        } else {
            // Add to error array
            $this->errors[$field][] = $msg;
        }
        return $this;
    }
*/
}