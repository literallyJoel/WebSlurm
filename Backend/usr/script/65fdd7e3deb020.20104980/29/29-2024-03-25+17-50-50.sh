#!/bin/bash
#SBATCH --job-name='COT2'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/29//slurmout

echo 'hmmmmm' > $out0
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/29/markcomplete > /dev/null 2>&1