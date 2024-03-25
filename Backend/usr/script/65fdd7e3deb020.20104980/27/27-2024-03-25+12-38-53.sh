#!/bin/bash
#SBATCH --job-name='1238'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/27//slurmout

file0=/home/sgjvivia/public_html/routes/../usr/in/65fdd7e3deb020.20104980/6601703e1dd97-extracted/file0
file1=/home/sgjvivia/public_html/routes/../usr/in/65fdd7e3deb020.20104980/6601703e1dd97-extracted/file1
diff -u "$file0" "$file1"
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/27/markcomplete > /dev/null 2>&1