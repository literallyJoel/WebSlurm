import { apiEndpoint } from "@/config/config";

export type ParameterType = "String" | "Number" | "Boolean" | "Undefined";
export type JobTypeParameter = {
  name: string;
  type: ParameterType;
  defaultValue: string | number | boolean | undefined;
};
export type CreateJobTypeRequest = {
  parameters: JobTypeParameter[];
  script: string;
  jobTypeName: string;
  jobTypeDescription: string;
  hasOutputFile: boolean;
  outputCount: number;
  hasFileUpload: boolean;
  arrayJobSupport: boolean;
  arrayJobCount: number;
};

export type CreateJobTypeResponse = {
  jobTypeId: string;
};

export type JobType = {
  jobTypeId: string;
  parameters: JobTypeParameter[];
  script: string;
  jobTypeName: string;
  jobTypeDescription: string;
  createdBy: string;
  createdByName: string;
  hasFileUpload: boolean;
  hasOutputFile: boolean;
  arrayJobSupport: boolean;
  arrayJobCount: number;
};
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

export const updateParameterList = (
  _parameterList: JobTypeParameter[],
  extractedParameters: string[]
): JobTypeParameter[] => {
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
  jobType: CreateJobTypeRequest,
  token: string
): Promise<CreateJobTypeResponse> => {
  const response = await fetch(`${apiEndpoint}/jobtypes/`, {
    method: "POST",
    body: JSON.stringify(jobType),
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  return await response.json();
};

export const getJobTypes = async (token: string): Promise<JobType[]> => {
  const response = await fetch(`${apiEndpoint}/jobtypes`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  return await response.json();
};

export const getJobType = async (
  jobTypeId: string,
  token: string
): Promise<JobType> => {
  const response = await fetch(`${apiEndpoint}/jobtypes/${jobTypeId}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  return await response.json();
};

export const updateJobType = async (
  jobTypeId: string,
  jobType: CreateJobTypeRequest,
  token: string
): Promise<Response> => {
  const response = await fetch(`${apiEndpoint}/jobtypes/${jobTypeId}`, {
    headers: { Authorization: `Bearer ${token}` },
    body: JSON.stringify(jobType),
    method: "PUT",
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  return await response;
};

export const deleteJobType = async (
  jobTypeId: string,
  token: string
): Promise<Response> => {
  const response = await fetch(`${apiEndpoint}/jobtypes/${jobTypeId}`, {
    headers: { Authorization: `Bearer ${token}` },
    method: "DELETE",
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  return await response;
};

export const validateParameters = (
  parameters: JobTypeParameter[]
): number[] => {
  const invalidIndices: number[] = [];
  parameters.forEach((parameter, index) => {
    if (parameter.type === "Undefined") {
      invalidIndices.push(index);
    }
  });
  return invalidIndices;
};
