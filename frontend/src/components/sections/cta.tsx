import { Icons } from "@/components/icons";
import Section from "@/components/section";
import { buttonVariants } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import Link from "next/link";

export default function CtaSection() {
  return (
    <Section
      id="cta"
      title="Bạn đã sẵn sàng trải nghiệm chưa?"
      subtitle="Vẽ để TRẺ - vẽ để KHOẺ - vẽ để ĐẸP"
      className="bg-primary/10 rounded-xl py-16"
    >
      <div className="flex flex-row w-full sm:flex-row items-center justify-center gap-4">
        <Link
          href="https://zalo.me/0968932498"
          target="_blank"
          className={cn(
            buttonVariants({ variant: "default" }),
            "w-auto text-background flex gap-2"
          )}
        >
          <Icons.logoZalo className="" />
          Liên hệ Zalo
        </Link>
        <Link
          href="/signup"
          className={cn(
            buttonVariants({ variant: "default" }),
            "w-auto text-background flex gap-2"
          )}
        >
          <Icons.logoThienDieuIcon className="h-6 w-6" />
          Đăng ký học viên
        </Link>
      </div>
    </Section>
  );
}
