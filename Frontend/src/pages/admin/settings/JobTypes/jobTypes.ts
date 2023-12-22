export type Job = {
  id: string;
  name: string;
  startTime: Date;
  endTime?: Date;
  runTime?: number;
};

export type Parameter = {
  name: string;
  type: ParameterType;
  default: string | number | boolean | undefined;
};

export type ParameterType = "String" | "Number" | "Boolean" | "Undefined";

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
        default: undefined,
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

export type CreateJobResponse = { jobTypeID: string };
export type JobType = {
  parameters: Parameter[];
  script: string;
  name: string;
  token: string;
};
export const createJobType = async (
  jobType: JobType
): Promise<CreateJobResponse> => {
  return (
    await fetch("api/jobtypes/create", {
      method: "POST",
      headers: {
        Authorization: `Bearer ${jobType.token}, Content-Type: application/json`,
      },
      body: JSON.stringify({
        parameters: jobType.parameters,
        name: jobType.name,
      }),
    })
  ).json();
};
