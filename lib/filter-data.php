<?php
/** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */

namespace Roots\Sage;

use DateInterval;
use Exception;
use WP_Query;

class FilterData {
	/**
	 * Obtains the available destinations.
	 *
	 * @return array List of available destinations or an empty list should they not exist.
	 */
	public static function get_destinations() {
		return self::get_taxonomy_terms('destination');
	}

	/**
	 * Obtains the available trip types.
	 *
	 * @return array List of available trip types or an empty list should they not exist.
	 */
	public static function get_trip_types() {
		return self::get_taxonomy_terms('trip-type');
	}

	/**
	 * Obtains the available age groups.
	 *
	 * @return array List of available age groups or an empty list should they not exist.
	 */
	public static function get_age_groups() {
		return self::get_taxonomy_terms('age-group');
	}

	/**
	 * Obtains the term name and slug for the provided taxonomy.
	 *
	 * @param string $taxonomy Taxonomy for which to obtain the terms.
	 * @return array Array of arrays containing
	 */
	private static function get_taxonomy_terms($taxonomy) {
		$terms = get_terms(array(
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
		));
		$data = array();
		if (is_wp_error($terms)) return $data;
		foreach ($terms as $term) {
			$data[] = array(
				'name' => $term->name,
				'slug' => $term->slug
			);
		}
		return $data;
	}

	/**
	 * Filters the templates based on the provided parameters
	 *
	 * @param array $filterDestination Destination filter in the form of an array of term slugs.
	 * @param array $filterTripType TripType filter in the form of an array of term slugs.
	 * @param string $filterKeywords Keyword filter in the form of a string of keywords.
	 * @param array $filterDateRange DateRange filter in the following format:
	 *  ['start_date' => <DateTime>, 'end_date' => <DateTime>]
	 * @return array List of results.
	 */
	public static function filter_results($filterDestination, $filterTripType, $filterKeywords, $filterDateRange) {
		$tax_query = [];
		$args = [
			'post_type'      => 'template',
			'posts_per_page' => -1,
			's'              => $filterKeywords,
			'fields'         => 'ids'
		];
		if ($filterDestination) {
			$tax_query[] = array(
				'taxonomy' => 'destination',
				'field' => 'slug',
				'terms' => $filterDestination,
			);
		}
		if ($filterTripType) {
			$tax_query[] = array(
				'taxonomy' => 'trip-type',
				'field' => 'slug',
				'terms' => $filterTripType,
			);
		}
		if ($filterDestination || $filterTripType) {
			$tax_query['relation'] = 'AND';
			$args['tax_query'] = $tax_query;
		}
		$postIds = get_posts($args);
		if (empty($postIds)) return [];
		$today = Helpers::today();

		$startDate = $today->format('Y-m-d');
		if (empty($filterDateRange)) {
			try {
				$endDate = $today->add(new DateInterval('P2Y'))->format('Y-m-d');
			} catch (Exception $e) {
				error_log("[FilterData->filter_results]: Could not parse the DateInterval.");
				return [];
			}
		} else {
			if (!empty($filterDateRange['start_date']) && $filterDateRange['start_date'] > $today) {
				$startDate = $filterDateRange['start_date']->format('Y-m-d');
			}
			$endDate = $filterDateRange['end_date']->format('Y-m-d');
		}
		global $wpdb;
        $query = sprintf('SELECT post_id, `trip_start_date`
            FROM `api_trip_template_pivot`
            INNER JOIN %s ON meta_value=template_assumax_id AND meta_key=\'_assumax_id\' AND post_id IN (%s)
            WHERE `trip_availability`!=\'Unavailable\' AND
            `trip_start_date`>=\'%s\' AND
            `trip_end_date`<=\'%s\'
            GROUP BY post_id ORDER BY `trip_start_date`;',
            $wpdb->postmeta, implode(',', $postIds), $startDate, $endDate);
		return $wpdb->get_col($query);
	}

	/**
	 * Checks the query vars for filter related variables and filters template results with them.
	 *
	 * @return array The resulting list of Template post ids.
	 */
	public static function handle_search_query_vars() {
		$filterDestinations = self::get_selected_destinations();
		$filterTripTypes = self::get_selected_types();
		$filterDateRange = self::get_selected_date_range();
		$filterKeywords = !empty($_GET['zoekterm']) ? sanitize_text_field($_GET['zoekterm']) : '';
		return self::filter_results($filterDestinations, $filterTripTypes, $filterKeywords, $filterDateRange);
	}

	/**
	 * Obtains any selected destinations from the query parameters.
	 *
	 * @return array Any selected destinations.
	 */
	public static function get_selected_destinations() : array
	{
		$filterDestination = [];
		if (!empty($_GET['bestemming']) && is_array($_GET['bestemming'])) {
			foreach ($_GET['bestemming'] as $destination) {
				$filterDestination[] = sanitize_text_field($destination);
			}
		}
		return $filterDestination;
	}

	/**
	 * Obtains any selected trip types from the query parameters.
	 *
	 * @return array Any selected types.
	 */
	public static function get_selected_types() : array
	{
		$filterTripTypes = [];
		if (!empty($_GET['type']) && is_array($_GET['type'])) {
			foreach ($_GET['type'] as $type) {
				$filterTripTypes[] = sanitize_text_field($type);
			}
		}
		return $filterTripTypes;
	}

	/**
	 * Obtains any selected date range from the query parameters.
	 *
	 * @return array Any selected date range.
	 */
	public static function get_selected_date_range() : array
	{
		$filterDateRange = [];
		if (!empty($_GET['datum'])) {
			if (is_array($_GET['datum']) && !empty($_GET['datum']['start']) && !empty($_GET['datum']['end'])) {
				$filterDateRange['start_date'] = Helpers::create_local_datetime($_GET['datum']['start']);
				$filterDateRange['end_date'] = Helpers::create_local_datetime($_GET['datum']['end']);
				if (empty($filterDateRange['start_date']) || empty($filterDateRange['end_date'])) {
					$filterDateRange = [];
				}
			} elseif (is_string($_GET['datum'])) {
				$dateElements = explode(' - ', $_GET['datum']);
				if (empty($dateElements) || count($dateElements) !== 2) {
					$filterDateRange = [];
				} else {
					$filterDateRange['start_date'] = Helpers::create_local_datetime($dateElements[0]);
					$filterDateRange['end_date'] = Helpers::create_local_datetime($dateElements[1]);
					if (empty($filterDateRange['start_date']) || empty($filterDateRange['end_date'])) {
						$filterDateRange = [];
					}
				}
			}
		}
		return $filterDateRange;
	}

	/**
	 * Creates a new search query filter link from the current query parameters and removes any unwanted elements
	 * provided in the parameters.
	 *
	 * @param string $destinationSlug Optional destination slug to remove from the search query.
	 * @param string $typeSlug Optional type slug to remove from the search query.
	 * @param bool $clearKeywords Whether or not to clear the keywords in the search query.
	 * @param bool $clearDateRange Whether or not to clear the date range in the search query.
	 * @return string The link to the new query.
	 */
	public static function make_new_filter_link(
		string $destinationSlug = '',
		string $typeSlug = '',
		bool $clearKeywords = false,
		bool $clearDateRange = false) : string
	{
		$selectedDestinations = self::get_selected_destinations();
		if (!empty($destinationSlug)) {
			$selectedDestinations = array_diff($selectedDestinations, [$destinationSlug]);
		}
		$selectedTypes = self::get_selected_types();
		if (!empty($typeSlug)) {
			$selectedTypes = array_diff($selectedTypes, [$typeSlug]);
		}
		$dateRangeFilter = '';
		if (!$clearDateRange) {
			$selectedDateRange = self::get_selected_date_range();
			if (!empty($selectedDateRange)) {
				$startDate = $selectedDateRange['start_date']->format('d-m-Y');
				$endDate = $selectedDateRange['end_date']->format('d-m-Y');
				$dateRangeFilter = "{$startDate} - {$endDate}";
			}
		}
		$keywordFilter = $clearKeywords ? '' : $_GET['zoekterm'] ?? '';
		return add_query_arg(
			[
				'zoekterm' => $keywordFilter,
				'bestemming' => $selectedDestinations,
				'type' => $selectedTypes,
				'datum' => $dateRangeFilter
			],
			site_url('zoeken')
		);
	}

	/**
	 * AJAX function that returns a count of the number of templates that pass the provided filters.
	 */
	public static function ajax_search_filter() {
		check_ajax_referer('search-form', 'security');
		wp_send_json(count(self::handle_search_query_vars()));
	}

	/**
	 * Disables the default search query.
	 *
	 * @param WP_Query $query The query object.
	 * @param bool $error
	 */
	public static function remove_default_search(WP_Query $query, bool $error = true) : void
	{
		if (is_search()) {
			$query->is_search = false;
		}
	}
}

// Ajax hooks.
add_action('wp_ajax_filter_results', [FilterData::class, 'ajax_search_filter']);
add_action('wp_ajax_nopriv_filter_results', [FilterData::class, 'ajax_search_filter']);
add_action('parse_query', [FilterData::class, 'remove_default_search']);
