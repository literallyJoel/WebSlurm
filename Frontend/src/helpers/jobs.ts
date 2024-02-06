export type JobInputParameter = {
  key: string;
  value: string | number | boolean;
};

export type JobInput = {
  jobID: number;
  jobName: string;
  parameters: JobInputParameter[];
};

export type CreateJobResponse = { output: string };

export type Job = {
  jobID: number;
  jobComplete: number | undefined;
  slurmID: number;
  jobTypeID: number;
  jobCompleteTime: number | undefined;
  jobStartTime: number;
  userID: string;
  jobName: string;
};
export async function createJob(
  job: JobInput,
  token: string
): Promise<CreateJobResponse> {
  return (
    await fetch("/api/jobs/create", {
      method: "POST",
      body: JSON.stringify(job),
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
}

export const getCompletedJobs = async (
  token: string,
  limit?: number,
  userID?: string
): Promise<Job[]> => {
  return (
    await fetch(`/api/jobs/complete?limit=${limit}&userID=${userID}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
};

export const getRunningJobs = async (
  token: string,
  limit?: number,
  userID?: string
): Promise<Job[]> => {
  return (
    await fetch(`/api/jobs/running?limit=${limit}&userID=${userID}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
};

export const getFailedJobs = async (
  token: string,
  limit?: number,
  userID?: string
): Promise<Job[]> => {
  return (
    await fetch(`/api/jobs/failed?limit=${limit}&userID=${userID}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
};
