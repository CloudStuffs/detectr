<?php

/**
 * The Item Model
 *
 * @author Faizan Ayubi
 */
class Item extends Shared\Model {

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
     * @validate required, alpha, min(3), max(100)
     * @label name
     */
    protected $_name;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     * @label description
     */
    protected $_description;

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
