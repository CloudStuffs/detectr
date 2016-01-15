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
     * @validate required, alpha, min(3), max(50)
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
     * @required
     */
    protected $_price;

    /**
     * @column
     * @readwrite
     * @type decimal
     * @length 10,2
     * @required
     */
    protected $_tax;

}
