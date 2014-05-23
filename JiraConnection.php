<?php
/**
 * @file - Simple PHP Class for connecting to Jira via REST API
 */

class JiraConnection {

  public $response;

  private $host;
  private $user;
  private $pass;

  public function __construct($host, $user, $pass) {
    $this->host = trim($host);
    $this->user = trim($user);
    $this->pass = trim($pass);
  }

  public function jql_query($jql) {
    if (is_string($jql)) {
      // replace @ with \\u0040, as @ is a reserved character in jql
      $request = 'jql=' . trim($jql);
      $request = preg_replace('/\s+/', '+', $request);

      $this->request($request);
    }
    else {
      throw(new Exception('Invalid jql statement'));
    }
  }

  /**
   * Make request to Jira instance and return results
   *
   * @param string request
   *   At this time, this is a string starting with jql= and followed
   *   by a valid jql query. @ symbols are reserved characters and it is
   *   the user's responsibility to wrap these in a string.
   */
  public function request($request) {
    error_log("request = " . $request);
    $ch = curl_init();
    curl_setopt_array($ch,
     array(
        CURLOPT_HTTPHEADER => array(
          'Content-Type: application/json',
          "Authorization: Basic " . base64_encode($this->user . ":" . $this->pass),
          ),
        CURLOPT_URL => $this->host . '/rest/api/latest/search?' . $request,
        CURLOPT_RETURNTRANSFER => 1,
        // CURLOPT_FAILONERROR => TRUE,
        // CURLOPT_USERPWD => "'" . $this->user . ":" . $this->pass . "'",
      ));
    if (!$this->response = curl_exec($ch)) {
      throw new Exception("Unable to process Jira JQL request: " . curl_error($ch));
    }
    $response = json_decode($this->response);
    if (!empty($response->errorMessages)) {
      throw new Exception("Able to reach Jira, but there was a problem with the request: " . print_r($response->errorMessages, 1));
    }

    error_log($this->host . '/rest/api/2/search?' . $request);
    curl_close($ch);
  }

  /**
   * Format a results object as HTML and return it
   * @param object results
   *
   * @return string HTML
   */
  public function format_as_html_table($results) {
    if (!is_array($results)) {
      return '<p>No results to format</p>';
    }

    $html = '<table border="1" cellpadding="0" class="query-results-table">';
    $keys = array_keys($results[0]);
    if (!empty($keys)) {
      $html .= "<thead><tr>";
      foreach ($keys as $header) {
        $html .= '<th>' . $header . '</th>';
      };
      $html .= "</tr></thead>";
    }

    foreach ($results as $item) {
      $html .= "<tr>";

      foreach ($item as $field) {
        $html .= '<td>' . $field . '</td>';
      }

      $html .= "</tr>";
    }

    $html .= '</table>';

    return $html;
  }

  /**
   * Parse JSON results into a more compact array of only the important data
   */
  public function parse_jql_results($response) {
    $results = array();
    foreach ($response->issues as $issue) {
      $results[] = array(
        'key' => '<a href="' . $this->host . '/browse/' . $issue->key . '">' . $issue->key . '</a>',
        'reporter' => $issue->fields->reporter->displayName,
        'title' => $issue->fields->summary,
        // 'description' => $issue->fields->description,
        'created' => date('j-d-Y', strtotime($issue->fields->updated)),
        );
    }
    return $results;
  }
}
