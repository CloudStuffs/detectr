<?php

/**
 * The Keyword Model
 *
 * @author Hemant Mann
 */
class Keyword extends Shared\Model {

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     *
     * @validate required
     */
    protected $_user_id;

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
     * @length 100
     * @index
     *
     * @validate required, min(3), max(100)
     */
    protected $_keyword;
}
