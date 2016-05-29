<?php
/**
 * Copyright (C) 2016  Martial Saunois
 *
 * Please read the LICENSE file at the root directory of the project for the full notice.
 */

namespace Martial\RelationshipsDataTransformer;

/**
 * Class RelationshipsDataTransformer
 * This class allows to organize database results with relationships in a more user friendly way.
 * For example, consider this SQL query:
 *
 * <code>
 * SELECT
 *   u.id AS user_id,
 *   u.username AS user_name,
 *   r.id AS role_id,
 *   r.name AS role_name,
 *   b.id AS book_id,
 *   b.name AS book_name
 * FROM
 *   users AS u
 * LEFT JOIN
 *   role AS r ON u.id = r.user_id
 * LEFT JOIN
 *   user_book AS ub ON u.id = ub.user_id
 * LEFT JOIN
 *   book AS b ON ub.book_id = b.id
 * </code>
 *
 * You run the query with PDO (or Doctrine DBAL, or whatever):
 *
 * <code>
 * $pdo = new \PDO($args);
 * $statement = $pdo->prepare($query);
 * $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
 * </code>
 *
 * At this point, you have as many rows as found relationships. So just call the transform method to organize the
 * data:
 *
 * <code>
 * use Martial\RelationshipsDataTransformer\RelationshipsDataTransformer;
 *
 * // ... the code above
 *
 * $transformer = new RelationshipsDataTransformer();
 * $reorganizedData = $transformer->transform($results, [
 *   RelationshipsDataTransformer::OPTION_ROOT_PRIMARY_KEY => 'user_id',
 *   RelationshipsDataTransformer::OPTION_RELATIONSHIPS => [
 *     'roles' => [
 *       RelationshipsDataTransformer::OPTION_PREFIX => 'role_',
 *       RelationshipsDataTransformer::OPTION_PRIMARY_KEY => 'role_id',
 *       RelationshipsDataTransformer::OPTION_REFERENCE_COLUMN => 'user_id'
 *     ],
 *     'books' => [
 *       RelationshipsDataTransformer::OPTION_PREFIX => 'book_',
 *       RelationshipsDataTransformer::OPTION_PRIMARY_KEY => 'book_id',
 *       RelationshipsDataTransformer::OPTION_REFERENCE_COLUMN => 'user_id'
 *     ],
 *   ]
 * ]);
 * </code>
 *
 * @package Martial\RelationshipsDataTransformer
 */
class RelationshipsDataTransformer implements DataTransformerInterface
{
    const OPTION_RELATIONSHIPS = 'relationships';
    const OPTION_PREFIX = 'prefix';
    const OPTION_REFERENCE_COLUMN = 'reference_column';
    const OPTION_ROOT_PRIMARY_KEY = 'root_primary_key';
    const OPTION_PRIMARY_KEY = 'primary_key';

    /**
     * Takes the database results as first argument and an array of options as second argument.
     * Returns the transformed data as array.
     *
     * @param array $data
     * @param array $options
     * @return array
     */
    public function transform(array $data, array $options)
    {
        $relationships = [];
        $result = [];
        $currentRootPrimaryKeyValue = null;
        $currentIndex = -1;

        foreach ($data as $row) {
            if ($currentRootPrimaryKeyValue !== $row[$options[self::OPTION_ROOT_PRIMARY_KEY]]) {
                $currentRootPrimaryKeyValue = $row[$options[self::OPTION_ROOT_PRIMARY_KEY]];
                $currentIndex++;
            }

            $this->extractRelationships($result, $relationships, $row, $currentIndex, $options);
        }

        $this->mergeResultsWithRelationships($result, $relationships, $options);

        return $result;
    }

    /**
     * Iterates over the row columns to extract the data that match the relationships defined in the options.
     *
     * @param array $result
     * @param array $relationships
     * @param array $row
     * @param int $currentIndex
     * @param array $options
     */
    private function extractRelationships(
        array &$result,
        array &$relationships,
        array $row,
        $currentIndex,
        array $options
    ) {
        foreach ($row as $column => $value) {
            $relationshipKey = '';
            $referenceColumn = '';
            $primaryKey = '';
            $prefix = '';

            if ($this->isRelationship($column, $options, $relationshipKey, $referenceColumn, $primaryKey, $prefix)) {
                $cleanedColumn = str_replace($prefix, '', $column);
                $relationships[$row[$referenceColumn]][$relationshipKey][$row[$primaryKey]][$cleanedColumn] = $value;
            } else {
                $result[$currentIndex][$column] = $value;
            }
        }
    }

    /**
     * Returns true if the column name matches a relationship, and stores the corresponding relationship data in the
     * arguments passed by references.
     *
     * @param string $column
     * @param array $options
     * @param string $relationshipKey
     * @param string $referenceColumn
     * @param string $primaryKey
     * @param string $prefix
     * @return bool
     */
    private function isRelationship(
        $column,
        array $options,
        &$relationshipKey,
        &$referenceColumn,
        &$primaryKey,
        &$prefix
    ) {
        foreach ($options[self::OPTION_RELATIONSHIPS] as $targetKey => $relationshipData) {
            if (0 === strpos($column, $relationshipData[self::OPTION_PREFIX])) {
                $relationshipKey = $targetKey;
                $referenceColumn = $relationshipData[self::OPTION_REFERENCE_COLUMN];
                $primaryKey = $relationshipData[self::OPTION_PRIMARY_KEY];
                $prefix = $relationshipData[self::OPTION_PREFIX];

                return true;
            }
        }

        return false;
    }

    /**
     * Merges the found relationships in the final result.
     *
     * @param array $results
     * @param array $relationships
     * @param array $options
     */
    private function mergeResultsWithRelationships(array &$results, array &$relationships, array $options)
    {
        $rootPrimaryKey = $options[self::OPTION_ROOT_PRIMARY_KEY];

        foreach ($results as $key => $row) {
            foreach ($relationships[$row[$rootPrimaryKey]] as $relationKey => $data) {
                $results[$key][$relationKey] = array_values($data);
            }
        }
    }
}
