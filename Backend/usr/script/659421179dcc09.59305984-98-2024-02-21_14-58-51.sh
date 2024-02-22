#!/bin/bash
#SBATCH --job-name='WC999'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/98

file0='/root/coding-projects/WebSlurm/Backend/routes/../usr/in/659421179dcc09.59305984/65d60efca113b'
wc -m < $file0
php ../../script/jobComplete.php 98