#!/bin/bash
#SBATCH --job-name='1236'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/26//slurmout

file0=/home/sgjvivia/public_html/routes/../usr/in/65fdd7e3deb020.20104980/66016fb7d9759
wc -c $file0
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/26/markcomplete > /dev/null 2>&1