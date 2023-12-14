import { useState } from "react";
import { Button } from "@/shadui/ui/button";
import { Label } from "@/shadui/ui/label";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/shadui/ui/select";
import { Input } from "@/shadui/ui/input";

interface props {
  onAddParameter: (parameterName: string, parameterType: string) => void;
  onClose: () => void;
}

const ParameterModal = ({ onAddParameter, onClose }: props) => {
  const [parameterName, setParameterName] = useState("");
  const [parameterType, setParameterType] = useState("string");

  const handleAddParameter = () => {
    onAddParameter(parameterName, parameterType);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black opacity-75"></div>
      <div className="z-10 bg-white p-6 rounded-md w-4/12 h-2/6">
        <div className="flex flex-col h-full">
          <div className="flex-grow">
            <Label htmlFor="parameter-name">Parameter Name</Label>
            <Input
              id="parameter-name"
              placeholder="Enter the parameter name"
              value={parameterName}
              onChange={(e) => setParameterName(e.target.value)}
            />

            <Label htmlFor="parameter-type">Parameter Type</Label>
            <Select onValueChange={(val) => setParameterType(val)}>
              <SelectTrigger>
                <SelectValue placeholder="Select a parameter type" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectItem value="str">Text</SelectItem>
                  <SelectItem value="num">Number</SelectItem>
                  <SelectItem value="bool">Checkbox</SelectItem>
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>

          <div className="flex justify-end mt-4">
            <Button className="mr-2" onClick={onClose}>
              Cancel
            </Button>
            <Button onClick={handleAddParameter}>Add Parameter</Button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ParameterModal;
