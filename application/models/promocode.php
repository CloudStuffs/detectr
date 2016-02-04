<?php

/**
 * The Promo Model
 *
 * @author Faizan Ayubi
 */
class PromoCode extends Shared\Model {

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
     * @type text
     * @length 20
     * @index
     */
    protected $_code;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_package_id;

    /**
     * @column
     * @readwrite
     * @type date
     * @label expiry date
     */
    protected $_expiry;

    /**
     * @column
     * @readwrite
     * @type integer
     * @label limit of use
     */
    protected $_limit;
}