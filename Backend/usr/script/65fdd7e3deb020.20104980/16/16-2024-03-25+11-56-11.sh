#!/bin/bash
#SBATCH --job-name='wc32424234234234'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/16//slurmout

file0=/home/sgjvivia/public_html/routes/../usr/in/65fdd7e3deb020.20104980/6601664ca2068
wc -c $file0
php /home/sgjvivia/public_html/routes/../script/jobComplete.php 16