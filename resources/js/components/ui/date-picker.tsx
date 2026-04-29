import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type Props = {
    name: string;
    defaultValue?: string | null;
    placeholder?: string;
};

export function DatePicker({ name, defaultValue, placeholder = 'Selecciona una fecha' }: Props) {
    const [date, setDate] = useState<Date | undefined>(
        defaultValue ? parseISO(defaultValue) : undefined,
    );
    const [open, setOpen] = useState(false);

    return (
        <>
            <input type="hidden" name={name} value={date ? format(date, 'yyyy-MM-dd') : ''} />
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        type="button"
                        variant="outline"
                        className={cn(
                            'w-full justify-start text-left font-normal',
                            !date && 'text-muted-foreground',
                        )}
                    >
                        <CalendarIcon className="mr-2 h-4 w-4 shrink-0" />
                        {date
                            ? format(date, 'PPP', { locale: es })
                            : placeholder}
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="start">
                    <Calendar
                        mode="single"
                        selected={date}
                        onSelect={(d) => {
                            setDate(d);
                            setOpen(false);
                        }}
                        captionLayout="dropdown"
                        fromYear={2015}
                        toYear={2035}
                        initialFocus
                    />
                </PopoverContent>
            </Popover>
        </>
    );
}
