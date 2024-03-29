#!/bin/bash
#SBATCH --job-name='AMT14'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/66041e3a579414.40635569/31//slurmout-%a
#SBATCH --array=0-2


arrayfile1="/root/coding-projects/WebSlurm/Backend/routes/../usr/in/66041e3a579414.40635569/6605a8d50e716-1-extracted/file${SLURM_ARRAY_TASK_ID}"

arrayfile0="/root/coding-projects/WebSlurm/Backend/routes/../usr/in/66041e3a579414.40635569/6605a8d50e716-extracted/file${SLURM_ARRAY_TASK_ID}"

echo $SLURM_ARRAY_TASK_ID
diff $arrayfile0 $arrayfile1
curl -X POST http://localhost:8080/api/jobs/31/markcomplete > /dev/null 2>&1