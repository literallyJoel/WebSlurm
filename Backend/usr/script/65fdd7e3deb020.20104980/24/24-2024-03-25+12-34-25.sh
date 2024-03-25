#!/bin/bash
#SBATCH --job-name='1234'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/24//slurmout

file0=/home/sgjvivia/public_html/routes/../usr/in/65fdd7e3deb020.20104980/66016f3fa405a
wc -c $file0
echo https://pgb.liv.ac.uk/~sgjvivia/api/jobs/24/markcomplete
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/24/markcomplete