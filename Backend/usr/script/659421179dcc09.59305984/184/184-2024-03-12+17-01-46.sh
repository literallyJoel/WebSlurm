#!/bin/bash
#SBATCH --job-name='DC'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/184//slurmout

file0=/root/coding-projects/WebSlurm/Backend/routes/../usr/in/659421179dcc09.59305984/65f08a694f423-extracted/file0
file1=/root/coding-projects/WebSlurm/Backend/routes/../usr/in/659421179dcc09.59305984/65f08a694f423-extracted/file1
# Check if the files exist and are readable
if [ ! -f $file0 ] || [ ! -r $file0 ]; then
  echo "File $file0 does not exist or is not readable"
  exit 2
fi

if [ ! -f $file1 ] || [ ! -r $file1 ]; then
  echo "File $file1 does not exist or is not readable"
  exit 3
fi

# Use the diff command to compare the files and output the differences
diff $file0 $file1
php /root/coding-projects/WebSlurm/Backend/routes/../script/jobComplete.php 184