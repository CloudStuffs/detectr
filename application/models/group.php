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
     * @validate required, max(50)
     */
    protected $_name;
    
    /**
     * @column
     * @readwrite
     * @type text
     */
    protected $_users;
}
