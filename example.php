<?php
/**
 * @file
 * Provides a simple example of using the JiraConnection class to
 * run a JQL query to retrieve all issues created today in Jira
 * and print results to screen.
 */

// Fill in your Jira instance details
define("JIRA_INSTANCE_URL", ""); // e.g., https://mysite.jira.com
define("JIRA_USERNAME", "");
define("JIRA_PASSWORD", "");

// create new Jira Connection
require_once('JiraConnection.php'); // Adjust path to class file
$jira = new JiraConnection(JIRA_INSTANCE_URL, JIRA_USERNAME, JIRA_PASSWORD);

// define a JQL query
$jql_query = "created > startOfDay() and created < endOfDay()";

// excecute the query
try {
  $jira->jql_query($jql_query);
} catch (Exception $e) {
  print $e->getMessage();
  die;
}

// do something with the response
$response = $jira->response;
$output = json_decode($response);

$raw_response = print_r($output, 1);


$output = $jira->parse_jql_results($output);
$output = $jira->format_as_html_table($output);

print $output;

print "<pre>";
print_r($raw_response);
print "</pre>";