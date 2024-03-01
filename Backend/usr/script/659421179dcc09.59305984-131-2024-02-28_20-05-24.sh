#!/bin/bash
#SBATCH --job-name='WC1'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/131

file0=/root/coding-projects/WebSlurm/Backend/routes/../usr/in/659421179dcc09.59305984/65df91a9bf931
wc -m < $file0
php ../../script/jobComplete.php 131