import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/shadui/ui/select";
import {
  isParameterType,
  JobTypeParameter,
} from "@/helpers/jobTypes";
import { Checkbox } from "@/components/shadui/ui/checkbox";
import { Label } from "@/components/shadui/ui/label";

interface props {
  parameters: JobTypeParameter[];
  index: number;
  setParameters: React.Dispatch<React.SetStateAction<JobTypeParameter[]>>;
  invalidParams: number[];
}

const ParameterEntry = ({
  parameters,
  index,
  setParameters,
  invalidParams,
}: props): JSX.Element => {
  return (
    <div className="flex flex-row w-full justify-evenly">
      <div className="w-1/4">
        <label className="text-sm font-medium w-1/3">
          {parameters[index].name}
        </label>
      </div>
      <div className="w-1/4">
        <Select
          required
          value={parameters[index].type}
          onValueChange={(e) => {
            if (isParameterType(e)) {
              const _parameters = [...parameters];
              _parameters[index].type = e;
              _parameters[index].defaultValue = undefined;
              setParameters(_parameters);
            }
          }}
        >
          <SelectTrigger
            className={`${
              invalidParams.includes(index) ? "border border-red-500" : ""
            }`}
          >
            <SelectValue placeholder="Select a parameter type" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="String">Text Input</SelectItem>
            <SelectItem value="Number">Number Input</SelectItem>
            <SelectItem value="Boolean">Checkbox Input</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div className="w-1/4">
        {parameters[index].type && parameters[index].type === "String" && (
          <input
            type="text"
            className="border border-gray-300 rounded-md"
            value={parameters[index].defaultValue as string}
            onChange={(e) => {
              const _parameters = [...parameters];
              _parameters[index].defaultValue = e.target.value;
              setParameters(_parameters);
            }}
          />
        )}

        {parameters[index].type && parameters[index].type === "Number" && (
          <input
            type="number"
            className="border border-gray-300 rounded-md"
            value={parameters[index].defaultValue as string}
            onChange={(e) => {
              const _parameters = [...parameters];
              _parameters[index].defaultValue = e.target.value;
              setParameters(_parameters);
            }}
          />
        )}

        {parameters[index].type && parameters[index].type === "Boolean" && (
          <>
            <Checkbox
              id="default"
              checked={parameters[index].defaultValue as boolean}
              onCheckedChange={(e) => {
                const _parameters = [...parameters];
                _parameters[index].defaultValue = e.valueOf();
                setParameters(_parameters);
              }}
            />
            <Label
              className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
              htmlFor="default"
            >
              Default Setting
            </Label>
          </>
        )}
      </div>
    </div>
  );
};

export default ParameterEntry;
