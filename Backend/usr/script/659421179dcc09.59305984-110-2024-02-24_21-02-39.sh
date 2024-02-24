#!/bin/bash
#SBATCH --job-name='WCasda'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/110

file0=/root/coding-projects/WebSlurm/Backend/routes/../usr/in/659421179dcc09.59305984/65da596232ff2
wc -m < $file0
php ../../script/jobComplete.php 110