<?php

/**
 * The Action Model
 *
 * @author Faizan Ayubi
 */
class Action extends Shared\Model {

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_user_id;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_trigger_id;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     * 
     * @validate required, min(3), max(32)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @label inputs
     */
    protected $_inputs;


    /**
     * @column
     * @readwrite
     * @type text
     * @label code to be outputed
     */
    protected $_code;
}
