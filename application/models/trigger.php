<?php

/**
 * The Trigger Model
 *
 * @author Faizan Ayubi
 */
class Trigger extends Shared\Model {

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
    protected $_website_id;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 2
     * 
     * @validate required, min(1), max(2)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @label meta
     */
    protected $_meta;
}
