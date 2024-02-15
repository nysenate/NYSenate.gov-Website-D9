Troubleshooting
===============

1. Chrome Crashed unexpectedly
------------------------------

When the shm size is too low, chrome is likely to crash randomly. 
The solution is increasing SHM size until the crashes no longer occur (or mounting /dev/shm:/dev/shm to the container)
Sometimes increasing SHM size is not possible (gitlab.com shared runners, for example gitlab-org/gitlab-runner#1454
Running with vendor/bin/behat --rerun || vendor/bin/behat --rerun is a good workaround for this and other hiccups. 
Since it'll cause behat to rerun only the failed scenarios and only fail the build if they fail again.
