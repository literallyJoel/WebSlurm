#!/bin/bash
#SBATCH --job-name='cdscsdcsdc'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/18//slurmout

file0=/home/sgjvivia/public_html/routes/../usr/in/65fdd7e3deb020.20104980/66016cbe22867
wc -c $file0
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/18/markcomplete >/dev/null 2>&1