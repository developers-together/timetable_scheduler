import React from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/input-otp'; // reuse; if you have a dedicated radio, swap import
import { Separator } from '@/components/ui/separator';

type Props = {
  open: boolean;
  onOpenChange: (v: boolean) => void;
  showMetrics: boolean;
  setShowMetrics: (v: boolean) => void;
  timeFormat: '12' | '24';
  setTimeFormat: (v: '12' | '24') => void;
};

export default function SettingsModal({
  open,
  onOpenChange,
  showMetrics,
  setShowMetrics,
  timeFormat,
  setTimeFormat,
}: Props) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Display Settings</DialogTitle>
        </DialogHeader>

        <div className="space-y-6">
          <div>
            <Label className="mb-2 block">Display metrics</Label>
            <RadioGroup
              value={showMetrics ? 'on' : 'off'}
              onValueChange={(v) => setShowMetrics(v === 'on')}
              className="grid grid-cols-2 gap-2"
            >
              <div className="flex items-center space-x-2 rounded-md border p-2">
                <RadioGroupItem value="on" id="metrics-on" />
                <Label htmlFor="metrics-on">On</Label>
              </div>
              <div className="flex items-center space-x-2 rounded-md border p-2">
                <RadioGroupItem value="off" id="metrics-off" />
                <Label htmlFor="metrics-off">Off</Label>
              </div>
            </RadioGroup>
          </div>

          <Separator />

          <div>
            <Label className="mb-2 block">Time format</Label>
            <RadioGroup
              value={timeFormat}
              onValueChange={(v) => setTimeFormat((v as '12' | '24') ?? '24')}
              className="grid grid-cols-2 gap-2"
            >
              <div className="flex items-center space-x-2 rounded-md border p-2">
                <RadioGroupItem value="12" id="time-12" />
                <Label htmlFor="time-12">12-hour</Label>
              </div>
              <div className="flex items-center space-x-2 rounded-md border p-2">
                <RadioGroupItem value="24" id="time-24" />
                <Label htmlFor="time-24">24-hour</Label>
              </div>
            </RadioGroup>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
