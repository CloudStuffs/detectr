<?php

/**
 * The Rank Model
 *
 * @author Hemant Mann
 */
class Rank extends Shared\Model {

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     *
     * @validate required
     */
    protected $_keyword_id;

	/**
     * @column
     * @readwrite
     * @type integer
     * @index
     *
     * @validate required
     */
    protected $_website_id;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 5
     * @index
     *
     * @validate required, min(3), max(5)
     */
    protected $_position;
}
