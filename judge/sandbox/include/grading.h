/**
 * Basic code grading mechanisms.
 */ 

#include <stdio.h>

static char token[33];

/**
 * Initailizes grading by creating an internal copy of the security token.
 */ 
static void begin_grading() {
  scanf("%32s", token);
}

/**
 * Sends a "points off" control message to Moodle.
 * Mark should be between 0-100.
 */ 
static void points_off(signed int mark, const char * message) {
  fprintf(stderr, "%s %d|%s\n", token, -mark, message);
}

/**
 * Sends a "comment" control message to Moodle.
 */ 
static void remark(const char * message) {
  points_off(0, message);
}

/**
 * Sends a "points" off message if the given condition is met.
 */
static void points_off_if(char condition, signed int mark, const char * message) {
  if(condition) {
    points_off(mark, message);  
  }
}

/**
 * Sends a "points" off message unless the given condition is met.
 */
static void points_off_unless(char condition, signed int mark, const char * message) {
  points_off_if(!condition, mark, message);
}
