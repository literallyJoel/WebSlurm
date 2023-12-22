import { useContext, useEffect, useState } from "react";
import {
  type Parameter,
  type ParameterType,
  extractParams,
  updateParamaterList,
  JobType,
  createJobType,
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

const NewJobType = (): JSX.Element => {
  const [script, setScript] = useState("");
  const [parameters, setParameters] = useState<Parameter[]>([]);
  const [invalidParams, setInvalidParams] = useState<number[]>([]);
  const [name, setName] = useState("");
  const [isNameValid, setIsNameValid] = useState(true);
  const { getToken } = useContext(AuthContext);
  const token = getToken();
  const createJobTypeRequest = useMutation(
    "createJobType",
    (jobType: JobType) => {
      return createJobType(jobType);
    }
  );

  const createJob = (): void => {
    const _invalidParams = validateParameters(parameters);
    setInvalidParams(_invalidParams);

    setIsNameValid(name !== "");
    if (_invalidParams.length === 0 && name !== "") {
      createJobTypeRequest.mutate({
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
          <CardTitle>Create a New Job Type</CardTitle>
          <CardDescription>
            Define a new Job Type by providing a name and bash script.
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
            onClick={() => createJob()}
          >
            Create Job Type
          </Button>
        </CardFooter>
      </Card>
    </div>
  );
};

export default NewJobType;
