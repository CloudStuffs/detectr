<?php

/**
 * The Subscription Model
 *
 * @author Faizan Ayubi
 */
class Subscription extends Shared\Model {

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
    protected $_item_id;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_period;

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
     * @type boolean
     * @label is promo code used in this
     */
    protected $_is_promo = false;

}
