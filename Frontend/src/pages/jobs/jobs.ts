export type JobParameter = {
  key: string;
  value: string | number | boolean;
};

export type Job = {
  jobID: string;
  parameters: JobParameter[];
};

export type CreateJobResponse = { output: string };

export async function createJob(
  job: Job,
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
