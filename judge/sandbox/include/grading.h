/**
 * Basic code grading mechanisms.
 */ 

//Never warn about these functions.
#pragma GCC diagnostic ignored "-Wunused-function"
#pragma GCC diagnostic ignored "-Wreturn-type"

#include <stdio.h>
#include <stdlib.h>

static char token[33];
static const char * HIDDEN = "";

/**
 * Initailizes grading by creating an internal copy of the security token.
 */ 
static void begin_grading() {
  scanf("%32s", token);
}

/**
 * Sends a "points off" control message to Moodle.
 * Marks are out of 100, and can be positive or negative.
 */ 
static void points_off(signed int mark, const char * message) {
  fprintf(stderr, "%s %d|%s\n", token, -mark, message);
}

/**
 * Sends an "award points" control message to moodle.
 * Marks are out of 100, and can be positive or negative.
 */
static void award_points(signed int mark, const char * message) {
  points_off(-mark, message);
}

/**
 * Sends a "comment" control message to Moodle.
 */ 
static void remark(const char * message) {
  points_off(0, message);
}

/**
 * Sends a "points off" message if the given condition is met.
 */
static void points_off_if(char condition, signed int mark, const char * message) {
  if(condition) {
    points_off(mark, message);  
  }
}

/**
 * Sends a "points off" message unless the given condition is met.
 */
static void points_off_unless(char condition, signed int mark, const char * message) {
  points_off_if(!condition, mark, message);
}

/**
 * Sends an "award points" message if the given condition is met.
 */
static void award_points_if(char condition, signed int mark, const char * message) {
  if(condition) {
    award_points(mark, message);  
  }
}

/**
 * Sends a "award points" message unless the given condition is met.
 */
static void award_points_unless(char condition, signed int mark, const char * message) {
  award_points_if(!condition, mark, message);
}


/**
 * Terminates grading. If add_one_hundred is set, a hundred points are added.
 * This statement forces the testbench to exit!
 */
static int end_grading(char add_one_hundred) {

  //If the add_one_hundred option is set, add 100 points.
  if(add_one_hundred) {
    award_points(100, HIDDEN);
  }

  //Force grading to terminate.
  exit(0);
  return 0;
}

/**
 * If the condition is met,
 * removes the given amount of points, and then forces the testbench to exit.
 * Should only be used in subtractive grading.
 */ 
static void fail_if(char condition, signed int mark, const char * message) {
    if(condition) {
        points_off(mark, message);
        end_grading(1);
    }
}

/**
 * Unless the given the condition is met,
 * removes the given amount of points, and then forces the testbench to exit.
 * Should only be used in subtractive grading.
 */ 
static void fail_unless(char condition, signed int mark, const char * message) {
    if(!condition) {
        points_off(mark, message);
        end_grading(1);
    }
}
