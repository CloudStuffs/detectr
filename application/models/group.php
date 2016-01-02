<?php

/**
 * Description of newsletter
 *
 * @author Hemant Mann
 */
class Group extends Shared\Model {
    /**
     * @column
     * @readwrite
     * @type text
     * @length 50
     * @index
     */
    protected $_name;
    
    /**
     * @column
     * @readwrite
     * @type text
     */
    protected $_users;
}
