import { Button } from "@/shadui/ui/button";
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/shadui/ui/card";
import React from "react";
import { MdErrorOutline } from "react-icons/md";

interface props {
  setView: React.Dispatch<
    React.SetStateAction<"create" | "success" | "failure">
  >;
}
export const CreationFailure = ({ setView }: props): JSX.Element => {
  return (
    <Card className="max-w-2xl mx-auto">
      <CardHeader>
        <CardTitle className="text-center">An error occurred</CardTitle>
      </CardHeader>
      <CardContent className="flex flex-col items-center">
        <MdErrorOutline size={60} className="text-red-500" />
        <span>Something went wrong. Please try again later.</span>
      </CardContent>
      <CardFooter className="justify-center">
        <Button
          onClick={() => setView("create")}
          className="w-5/12 border-uol border-2 rounded-xl shadow-2xl hover:text-white hover:bg-uol hover:shadow-inner"
        >
          Back
        </Button>
      </CardFooter>
    </Card>
  );
};
