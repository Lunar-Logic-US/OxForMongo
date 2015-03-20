<?php
class TestSchema extends Ox_Schema
{
    /**
     * Create the schema from the given array
     * @param mixed $schema
     */
    public function __construct($schema=false)
    {
        if ($schema !== false) {
            $this->setSchema($schema);
        }
    }

    /**
     * Setter for _schema
     * @param array $schema
     */
    public function setSchema(array $schema)
    {
        $this->_schema = $schema;
    }

    /**
     * Setter for _type
     * @param string $type
     */
    public function setType($type='array')
    {
        $this->_type = $type;
    }

    /**
     * Setter for _onInsert
     * @param string $type
     */
    public function setOnInsert($bool=false)
    {
        $this->_onInsert = $bool;
    }

}
