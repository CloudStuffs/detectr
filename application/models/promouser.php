<?php

/**
 * The PromoUser Model
 *
 * @author Faizan Ayubi
 */
class PromoUser extends Shared\Model {

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
    protected $_promocode_id;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 20
     * @index
     */
    protected $_code;
}