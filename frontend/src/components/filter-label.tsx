import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils";

interface FormGroupProps {
  label: string | React.ReactNode;
  children: React.ReactNode;
  className?: string;
  htmlFor?: string;
}

export const FormGroup = ({
  label,
  children,
  className,
  htmlFor,
}: FormGroupProps) => {
  return (
    <div className={cn("grid gap-2", className)}>
      <Label htmlFor={htmlFor}>{label}</Label>
      {children}
    </div>
  );
};
