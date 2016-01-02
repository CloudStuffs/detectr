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
     * @label period in days
     */
    protected $_period;

    /**
     * @column
     * @readwrite
     * @type date
     * @label expiry date
     */
    protected $_expiry;

}
