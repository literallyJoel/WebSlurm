#!/bin/bash
#SBATCH --job-name='WCA2'
#SBATCH --output=/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/out/660c12855d5262.85990556/19//slurmout-%a
#SBATCH --array-0--1


arrayfile0="/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/in/660c12855d5262.85990556/19660d377a41bf3-extracted/file${SLURM_ARRAY_TASK_ID}"

wc -c $arrayfile
curl -X POST http://localhost:8080/api/jobs/19/markcomplete > /dev/null 2>&1