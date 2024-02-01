import { useContext, useState } from "react";
import {
  type Parameter,
  extractParams,
  updateParamaterList,
  JobType,
  getJobType,
  updateJobType,
  JobTypeUpdate,
} from "../jobTypes";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/shadui/ui/card";
import { Label } from "@radix-ui/react-label";
import { Input } from "@/shadui/ui/input";
import { Editor } from "@monaco-editor/react";
import ParameterEntry from "../components/ParameterEntry";
import { Button } from "@/shadui/ui/button";
import { validateParameters } from "@/helpers/validation";
import { useMutation } from "react-query";
import { AuthContext } from "@/providers/AuthProvider/AuthProvider";
import { useLoaderData } from "react-router-dom";

export async function loader({ params  }: any) {
  const jobType: JobType = await getJobType(
    localStorage.getItem("token") ?? "",
    params.id
  );
  return jobType;
}

export const UpdateJobType = (): JSX.Element => {
  const jobType = useLoaderData() as JobType;
  const [script, setScript] = useState(jobType.script);
  const [parameters, setParameters] = useState<Parameter[]>(jobType.parameters);
  const [invalidParams, setInvalidParams] = useState<number[]>([]);
  const [name, setName] = useState(jobType.name);
  const [isNameValid, setIsNameValid] = useState(true);
  const token = useContext(AuthContext).getToken();
  const updateJobTypeRequest = useMutation(
    "updateJobType",
    (jobType: JobTypeUpdate) => {
      return updateJobType(jobType);
    }
  );

  const updateJob = (): void => {
    const _invalidParams = validateParameters(parameters);
    setInvalidParams(_invalidParams);

    setIsNameValid(name !== "");
    if (_invalidParams.length === 0 && name !== "") {
      updateJobTypeRequest.mutate({
        id: jobType.id,
        name: name,
        script: script,
        parameters: parameters,
        token: token,
      });
    }
  };

  return (
    <div className="flex flex-col w-full items-center">
      <Card className="w-full max-w-2xl">
        <CardHeader>
          <CardTitle>Edit Job Type</CardTitle>
          <CardDescription>
            Parameters that users will enter through the site should be written
            using {"{{parameterName}}"} format.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="jobName">Job Name</Label>
            <Input
              id="jobName"
              placeholder="Enter the Job Type Name"
              value={name}
              className={`${isNameValid ? "" : "border-red-500"}`}
              onChange={(e) => setName(e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="script">Bash Script</Label>
            <br />
            <Label className="text-sm text-red-500">
              The application assumes all code entered here is trusted. It is
              your responsibility to upload safe code.
            </Label>
            <Editor
              height="300px"
              theme="vs-dark"
              value={script}
              onChange={(value) => {
                setScript(value ?? "");
                setParameters((prev) =>
                  updateParamaterList(prev, extractParams(value ?? ""))
                );
              }}
            />
          </div>

          <div>
            <Label htmlFor="parameters">Parameters</Label>
            <div className="flex flex-row w-full justify-evenly mb-2 border-b-2">
              <div className="w-1/4">
                <label className="text-sm font-medium w-1/3">Name</label>
              </div>
              <div className="w-1/4">
                <label className="text-sm font-medium w-1/3">Type</label>
              </div>
              <div className="w-1/4">
                <label className="text-sm font-medium w-1/3">Default</label>
              </div>
            </div>
            {parameters.map((param, index) => (
              <div className="p-1">
                <ParameterEntry
                  key={`${param.name}-${param.type}`}
                  parameters={parameters}
                  index={index}
                  setParameters={setParameters}
                  invalidParams={invalidParams}
                />
              </div>
            ))}
          </div>
        </CardContent>
        <CardFooter className="justify-center p-4">
          <Button
            className="bg-transparent border border-uol text-uol hover:bg-uol hover:text-white"
            onClick={() => updateJob()}
          >
            Update Job Type
          </Button>
        </CardFooter>
      </Card>
    </div>
  );
};

