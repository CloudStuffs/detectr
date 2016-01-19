<?php

/**
 * The Package Model
 *
 * @author Faizan Ayubi
 */
class Package extends Shared\Model {

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     * @validate required, numeric
     */
    protected $_user_id;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 100
     * 
     * @validate required, min(3), max(100)
     * @label name
     */
    protected $_name;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     * @label json encode of item ids
     * @validate required
     */
    protected $_item;

    /**
     * @column
     * @readwrite
     * @type decimal
     * @length 10,2
     * @validate required
     */
    protected $_price;

    /**
     * @column
     * @readwrite
     * @type decimal
     * @length 10,2
     * @validate required
     */
    protected $_tax;

    /**
     * @column
     * @readwrite
     * @type integer
     * @label period in days
     */
    protected $_period = 30;
}
