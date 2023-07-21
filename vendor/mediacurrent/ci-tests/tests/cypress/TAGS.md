Existing tags
===========================================

Entire suites or individual tests can be tagged as follows to assist in running tests:

Tags to assist in selectively running tests
===========================================

@smoke
    A broad selection of key tests that can be run quickly to make sure you haven't broken anything.
   
@p1
	Priority 1 test cases.

@ci
    Used to include a feature or scenario in the continuous integration system.

@drush
   Indicate the test requires drush to be run. May be limited to local DDEV environments.

@slow
    Used for tests that are, well, slow. There is not, as yet, a definitive metric like, requires more than 10 seconds to complete.

@wip
    Used to tag a work-in-progress. These are typically excluded from a test run as they may fail or do not yet fully test the feature described.

@anon
    Used for suites/tests that don't require user authentication to run the test.
    
@auth
    Used for suites/tests that require user authentication to run the test.

@specific_text
    Denotes tests that are looking at text that is more likely to change and require update.

@bug
    Known bug.  Don’t execute under normal runs.  Similar to Skip.

Section Tags:
=============

The tags below are used to identify where a suite (not an individual test) is located within the site map.

@front

Featureset Tags
===============
Featureset tags describe features that don't correspond to a traditional site map structure or are complex features at a lower level on the site.

@admin
@user

Introducing new tags
********************
Any new tags that are committed to the repository should be documented here.


