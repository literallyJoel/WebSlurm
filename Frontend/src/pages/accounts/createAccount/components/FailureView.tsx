//=======================================//
//=============Form Inputs==============//
//=====================================//
/*
The ShadCN/UI library has been used for many of the UI elements, which are imported helper
https://ui.shadcn.com/docs
*/
import {
  CardTitle,
  CardHeader,
  CardContent,
  CardFooter,
  Card,
} from "@/shadui/ui/card";
import { Button } from "@/shadui/ui/button";
import { MdErrorOutline } from "react-icons/md";


export const CreationFailedView = (): JSX.Element => {
  //=======================================//
  //===============UI Code================//
  //=====================================//
  return (
    <Card className="max-w-2xl mx-auto">
      <CardHeader>
        <CardTitle className="text-center">Error</CardTitle>
      </CardHeader>
      <CardContent className="flex flex-col items-center">
        <MdErrorOutline size={60} className="text-red-500" />
        <span>Something went wrong. Please try again later.</span>
      </CardContent>
      <CardFooter className="justify-center">
        <Button className="w-5/12 border-uol border-2 rounded-xl shadow-2xl hover:text-white hover:bg-uol hover:shadow-inner">
          Back
        </Button>
      </CardFooter>
    </Card>
  );
};