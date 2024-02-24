#!/bin/bash
#SBATCH --job-name='WCMA'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/111

file0=/root/coding-projects/WebSlurm/Backend/routes/../usr/in/659421179dcc09.59305984/65da59b46c590
wc -m < $file0
php ../../script/jobComplete.php 111