export type Parameter = {
  name: string;
  type: ParameterType;
  defaultValue: string | number | boolean | undefined;
};

export type ParameterType = "String" | "Number" | "Boolean" | "Undefined";
export type CreateJobResponse = { jobTypeID: string };
export type JobTypeCreation = {
  parameters: Parameter[];
  script: string;
  name: string;
  description: string;
  fileUploadCount: number;
  imgUploadCount: number;
  token: string;
};

export type JobType = {
  id: number;
  parameters: Parameter[];
  script: string;
  description: string;
  name: string;
  createdBy: string;
  createdByName: string;
  fileUploadCount: number;
  imgUploadCount: number;
};

export type JobTypeUpdate = JobTypeCreation & { id: number };

export const isParameterType = (str: string): str is ParameterType => {
  return ["String", "Number", "Boolean", "Undefined"].includes(str);
};

export const extractParams = (script: string): string[] => {
  const pattern = /{{(.*?)}}/g;
  const matches = script.match(pattern);
  return matches
    ? [...new Set(matches.map((match) => match.replace(/{{|}}/g, "")))]
    : [];
};

export const updateParamaterList = (
  _parameterList: Parameter[],
  extractedParameters: string[]
): Parameter[] => {
  const parameterList = [..._parameterList];

  const parameterSet = new Set(
    parameterList.map((parameter) => parameter.name)
  );

  extractedParameters.forEach((extractedName) => {
    if (!parameterSet.has(extractedName)) {
      parameterList.push({
        name: extractedName,
        type: "Undefined",
        defaultValue: undefined,
      });
      parameterSet.add(extractedName);
    }
  });

  parameterList.forEach((parameter) => {
    if (!extractedParameters.includes(parameter.name)) {
      parameterList.splice(parameterList.indexOf(parameter), 1);
    }
  });
  return parameterList;
};

export const createJobType = async (
  jobType: JobTypeCreation
): Promise<CreateJobResponse> => {
  console.log(jobType.token);
  return (
    await fetch("/api/jobtypes/create", {
      method: "POST",
      headers: {
        Authorization: `Bearer ${jobType.token}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify(jobType)
    })
  ).json();
};

export const getJobTypes = async (token: string): Promise<JobType[]> => {
  return (
    await fetch("/api/jobtypes", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
};

export const getJobType = async (
  token: string,
  id: string
): Promise<JobType> => {
  return (
    await fetch(`/api/jobtypes/${id}`, {
      method: "GET",
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
};

export const updateJobType = async (
  jobType: JobTypeUpdate
): Promise<{ ok: boolean }> => {
  return await fetch(`/api/jobtypes/${jobType.id}`, {
    method: "PUT",
    headers: {
      Authorization: `Bearer ${jobType.token}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify(jobType),
  });
};

export const deleteJobType = async (jobTypeID: string, token: string) => {
  return await fetch(`/api/jobtypes/${jobTypeID}`, {
    method: "DELETE",
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });
};
