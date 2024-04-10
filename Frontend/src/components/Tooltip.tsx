import {
  TooltipProvider,
  Tooltip as ShadTooltip,
  TooltipTrigger,
  TooltipContent,
} from "./shadui/ui/tooltip";

interface props {
  text: string;
  children: React.ReactNode;
}

const Tooltip = ({ text, children }: props): JSX.Element => {
  return (
    <TooltipProvider>
      <ShadTooltip>
        <TooltipTrigger>{children}</TooltipTrigger>
        <TooltipContent>{text}</TooltipContent>
      </ShadTooltip>
    </TooltipProvider>
  );
};

export default Tooltip;
