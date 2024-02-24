#!/bin/bash
#SBATCH --job-name='FTDSFDS'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/112

file0=/root/coding-projects/WebSlurm/Backend/routes/../usr/in/659421179dcc09.59305984/65da59c4ac774
type=$(file --mime-type -b "$file0")
format=${type#*/}
echo "File is $format"
php ../../script/jobComplete.php 112