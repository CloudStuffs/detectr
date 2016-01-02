<?php

/**
 * The Transaction Model
 *
 * @author Faizan Ayubi
 */
class Transaction extends Shared\Model {

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
     * @length 255
     * 
     * @validate required, alpha, min(3), max(32)
     * @label reference id from paypal
     */
    protected $_ref_id;

    /**
     * @column
     * @readwrite
     * @type decimal
     * @length 10,2
     * 
     * @validate required
     * @label It is the domain only (like playmusic.net)
     */
    protected $_amount;

}
