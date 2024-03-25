#!/bin/bash
#SBATCH --job-name='hmmmmmm'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/17//slurmout

file0=/home/sgjvivia/public_html/routes/../usr/in/65fdd7e3deb020.20104980/66016c3603322
wc -c $file0
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/17/markcomplete