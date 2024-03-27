#!/bin/bash
#SBATCH --job-name='cot6'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/32//slurmout

echo 'hmmmmmmmmmmmmm' > $out0
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/32/markcomplete > /dev/null 2>&1