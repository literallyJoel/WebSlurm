#!/bin/bash
#SBATCH --job-name='COTest'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/28//slurmout

echo 'hmmm' > $out0
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/28/markcomplete > /dev/null 2>&1