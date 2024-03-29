#!/bin/bash
#SBATCH --job-name='AMT13'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/66041e3a579414.40635569/30//slurmout
#SBATCH --array=0-2


arrayfile1="/root/coding-projects/WebSlurm/Backend/routes/../usr/in/66041e3a579414.40635569/6605a813bb057-1-extracted/file${SLURM_ARRAY_TASK_ID}"

arrayfile0="/root/coding-projects/WebSlurm/Backend/routes/../usr/in/66041e3a579414.40635569/6605a813bb057-extracted/file${SLURM_ARRAY_TASK_ID}"

echo $SLURM_ARRAY_TASK_ID
diff $arrayfile0 $arrayfile1
curl -X POST http://localhost:8080/api/jobs/30/markcomplete > /dev/null 2>&1