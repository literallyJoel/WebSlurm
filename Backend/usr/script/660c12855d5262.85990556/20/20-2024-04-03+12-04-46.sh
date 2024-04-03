#!/bin/bash
#SBATCH --job-name='WCA3'
#SBATCH --output=/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/out/660c12855d5262.85990556/20//slurmout-%a
#SBATCH --array-0-11


arrayfile0="/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/in/660c12855d5262.85990556/20660d377a41bf3-extracted/file${SLURM_ARRAY_TASK_ID}"

wc -c $arrayfile
curl -X POST http://localhost:8080/api/jobs/20/markcomplete > /dev/null 2>&1