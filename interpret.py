import sys, argparse, re
import xml.etree.ElementTree as ET


def replaceEscape(match):
    number = int(match.group(1))
    return chr(number)


class Interpet(object):
    """docstring for Interpet"""

    def __init__(self):
        super(Interpet, self).__init__()
        self.var = r"^(GF|LF|TF)@([A-Za-z\-\_\*\$%&][\w\-\*\_\$%&]*)$"  # regularni vyrazy ktere budou pouzity pro lexikalni kontrolu argumentu
        self.integer = r"^[+-]?\d+$"
        self.boolean = r"^(true|false)$"
        self.label = r"^[A-Za-z\-\_\*\$%&][\w\-\_\*\$%&]*$"
        self.string = r"^((\x5C\d{3})|[^\x23\s\x5C])*$"
        self.symb = r"var|string|bool|int"
        self.typ = r"int|string|bool"
        self.tempframe = None
        self.framearray = []
        self.localframe = None
        self.globalframe = {}
        self.labels = {}
        self.callarray = []
        self.dataarray = []
        self.parsearg()  # volani funkce pro spracovani vstupnich argumentu
        self.xmlread()  # volani funkce pro nacitani xml souboru
        self.iodercontrol()  # volani funkce pro kontrolu postupnosti a opakovani orderu
        self.iopcodecontrol()  # prvni beh programu, volani funkce pro kontrolu jednotlivych opcode, jedna se o syntaktickou a lexikalni analyzu
        self.iperform()  # druhy beh programu, volani funkce pro semantickou analyzu

    def parsearg(self):  # spracovani vstupnich argumentu
        parser = argparse.ArgumentParser()
        parser.add_argument('--source',
                            help='Skript typu filtr (parse.php v jazyce PHP 5.6) nacte ze standardniho vstupu zdrojovy kod v IPPcode18 (viz sekce 6), zkontroluje lexikalni a syntaktickou spravnost kodu a vypise na standardni vystup XML reprezentaci programu dle specifikace v sekci 3.1.\n',
                            required=True)
        try:
            self.args = parser.parse_args()
        except:
            exit(10)

    def xmlread(self):  # nacitani xml souboru
        tree = ET.parse(self.args.source)
        try:
            self.root = tree.getroot()
        except FileNotFoundError:
            exit(11)
        except:
            exit(31)

        if self.root.tag != 'program' or self.root.get('language') != 'IPPcode19':  # kontrola spravnosti hlavicky
            exit(31)

    def iodercontrol(self):
        elemorder = []
        for elem in self.root:
            if elemorder.count(elem.get('order')) > 0:  # funkce pro kontrolu postupnosti a opakovani orderu
                exit(32)
            elemorder.append(elem.get('order'))

    def checksymbol(self, typ, text):  # pomocna funkce pro lexikalni kontrolu symbolu
        if typ == 'var' and text == None:
            exit(32)
        elif typ == 'var' and re.fullmatch(self.var, text) == None:
            exit(31)
        elif typ == 'string' and text == None:
            pass
        elif typ == 'string' and re.fullmatch(self.string, text) == None:
            exit(31)
        elif typ == 'bool' and text == None:
            exit(32)
        elif typ == 'bool' and re.fullmatch(self.boolean, text) == None:
            exit(31)
        elif typ == 'int' and text == None:
            exit(32)
        elif typ == 'int' and re.fullmatch(self.integer, text) == None:
            exit(31)

    def checkvar(self, text):  # pomocna funkce pro lexikalni kontrolu variable
        if text == None:
            exit(32)
        elif re.fullmatch(self.var, text) == None:
            exit(31)

    def checklabel(self, text):  # pomocna funkce pro lexikalni kontrolu label
        if text == None:
            exit(32)
        elif re.fullmatch(self.label, text) == None:
            exit(31)

    def checktype(self, text):  # pomocna funkce pro lexikalni kontrolu type
        if text == None:
            exit(32)
        elif re.fullmatch(self.typ, text) == None:
            exit(31)

    def returnsymboltype(self, typ, text):
        if typ == 'bool':
            if text == 'true':
                return True
            elif text == 'false':
                return False
        elif typ == 'int':
            return int(text)
        elif typ == 'string':
            if text is None:
                return ""
            else:
                regex = re.compile(r"\\(\d{3})")
                return regex.sub(replaceEscape, text)
        elif typ == 'var':
            match = re.fullmatch(self.var, text)
            if match.group(1) == 'LF':
                if self.localframe == None:
                    exit(55)
                else:
                    if match.group(2) not in self.localframe:
                        exit(54)
                    else:
                        if self.localframe.get(match.group(2)) == None:
                            exit(56)
                        else:
                            return self.localframe.get(match.group(2))
            elif match.group(1) == 'GF':
                if self.globalframe == None:
                    exit(55)
                else:
                    if match.group(2) not in self.globalframe:
                        exit(54)
                    else:
                        if self.globalframe.get(match.group(2)) == None:
                            exit(56)
                        else:
                            return self.globalframe.get(match.group(2))
            elif match.group(1) == 'TF':
                if self.tempframe == None:
                    exit(55)
                else:
                    if match.group(2) not in self.tempframe:
                        exit(54)
                    else:
                        if self.tempframe.get(match.group(2)) == None:
                            exit(56)
                        else:
                            return self.tempframe.get(match.group(2))

    def setvar(self, var, text):
        match = re.fullmatch(self.var, var)
        if match.group(1) == 'LF':
            if self.localframe == None:
                exit(55)
            else:
                if match.group(2) not in self.localframe:
                    exit(54)
                else:
                    self.localframe[match.group(2)] = text
        elif match.group(1) == 'GF':
            if self.globalframe == None:
                exit(55)
            else:
                if match.group(2) not in self.globalframe:
                    exit(54)
                else:
                    self.globalframe[match.group(2)] = text
        elif match.group(1) == 'TF':
            if self.tempframe == None:
                exit(55)
            else:
                if match.group(2) not in self.tempframe:
                    exit(54)
                else:
                    self.tempframe[match.group(2)] = text

    def checksamesymbol(self, symb1value, symb2value):
        if isinstance(symb1value, bool) == True and isinstance(symb2value, bool) == True:
            return True
        elif isinstance(symb1value, int) == True and isinstance(symb2value, int) == True and not isinstance(symb1value,
                                                                                                            bool) == True and not isinstance(
                symb2value, bool) == True:
            return True
        elif isinstance(symb1value, str) == True and isinstance(symb2value, str) == True:
            return True
        else:
            return False

    def iopcodecontrol(
            self):  # Kontrola opcode z moznych variant s vnorenou syntaktickou a lexikalni analyzou pro kazdy opcode
        i = 0
        for elem in self.root:
            i = i + 1
            opcode = elem.get('opcode')
            if opcode == 'MOVE':
                if len(elem) != 2:  # kontrola poctu argumentu
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[
                        0].tag != 'arg1':  # kontrola typu a pozice prvniho argumentu
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[
                        1].tag != 'arg2':  # kontrola typu a pozice druheh argumentu
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)  # volani funkce pro lexikalni kontrolu prvniho argumentu
                        self.checksymbol(elem[1].get('type'),
                                         elem[1].text)  # volani funkce pro lexikalni kontrolu druheho argumentu
            elif opcode == 'CREATEFRAME':
                if len(elem) != 0:
                    exit(31)
            elif opcode == 'PUSHFRAME':
                if len(elem) != 0:
                    exit(31)
            elif opcode == 'POPFRAME':
                if len(elem) != 0:
                    exit(31)
            elif opcode == 'DEFVAR':
                if len(elem) != 1:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
            elif opcode == 'CALL':
                if len(elem) != 1:
                    exit(31)
                else:
                    if elem[0].get('type') != 'label' or elem[0].tag != 'arg1':
                        exit(31)
                    else:
                        self.checklabel(elem[0].text)
            elif opcode == 'RETURN':
                if len(elem) != 0:
                    exit(31)
            elif opcode == 'PUSHS':
                if len(elem) != 1:
                    exit(31)
                else:
                    if re.fullmatch(self.symb, elem[0].get('type')) == None or elem[0].tag != 'arg1':
                        exit(31)
                    else:
                        self.checksymbol(elem[0].get('type'), elem[0].text)
            elif opcode == 'POPS':
                if len(elem) != 1:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
            elif opcode == 'ADD':
                if len(elem) != 3:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[2].get('type')) == None or elem[2].tag != 'arg3':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
                        self.checksymbol(elem[2].get('type'), elem[2].text)
            elif opcode == 'SUB':
                if len(elem) != 3:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[2].get('type')) == None or elem[2].tag != 'arg3':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
                        self.checksymbol(elem[2].get('type'), elem[2].text)
            elif opcode == 'MUL':
                if len(elem) != 3:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[2].get('type')) == None or elem[2].tag != 'arg3':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
                        self.checksymbol(elem[2].get('type'), elem[2].text)
            elif opcode == 'IDIV':
                if len(elem) != 3:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[2].get('type')) == None or elem[2].tag != 'arg3':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
                        self.checksymbol(elem[2].get('type'), elem[2].text)
            elif opcode == 'LT' or opcode == 'GT' or opcode == 'EQ':
                if len(elem) != 3:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[2].get('type')) == None or elem[2].tag != 'arg3':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
                        self.checksymbol(elem[2].get('type'), elem[2].text)
            elif opcode == 'AND' or opcode == 'OR':
                if len(elem) != 3:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[2].get('type')) == None or elem[2].tag != 'arg3':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
                        self.checksymbol(elem[2].get('type'), elem[2].text)
            elif opcode == 'NOT':
                if len(elem) != 2:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
            elif opcode == 'INT2CHAR':
                if len(elem) != 2:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
            elif opcode == 'STRI2INT':
                if len(elem) != 3:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[2].get('type')) == None or elem[2].tag != 'arg3':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
                        self.checksymbol(elem[2].get('type'), elem[2].text)
            elif opcode == 'READ':
                if len(elem) != 2:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif elem[1].get('type') != 'type' or elem[1].tag != 'arg2':
                        exit(31)
                    self.checkvar(elem[0].text)
                    self.checktype(elem[1].text)
            elif opcode == 'WRITE':
                if len(elem) != 1:
                    exit(31)
                else:
                    if re.fullmatch(self.symb, elem[0].get('type')) == None or elem[0].tag != 'arg1':
                        exit(31)
                    else:
                        self.checksymbol(elem[0].get('type'), elem[0].text)
            elif opcode == 'CONCAT':
                if len(elem) != 3:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[2].get('type')) == None or elem[2].tag != 'arg3':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
                        self.checksymbol(elem[2].get('type'), elem[2].text)
            elif opcode == 'STRLEN':
                if len(elem) != 2:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
            elif opcode == 'GETCHAR':
                if len(elem) != 3:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[2].get('type')) == None or elem[2].tag != 'arg3':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
                        self.checksymbol(elem[2].get('type'), elem[2].text)
            elif opcode == 'SETCHAR':
                if len(elem) != 3:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[2].get('type')) == None or elem[2].tag != 'arg3':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
                        self.checksymbol(elem[2].get('type'), elem[2].text)
            elif opcode == 'TYPE':
                if len(elem) != 2:
                    exit(31)
                else:
                    if elem[0].get('type') != 'var' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    else:
                        self.checkvar(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
            elif opcode == 'LABEL':
                if len(elem) != 1:
                    exit(31)
                else:
                    if elem[0].get('type') != 'label' or elem[0].tag != 'arg1':
                        exit(31)
                    else:
                        self.checklabel(elem[0].text)
                        if elem[0].text in self.labels:
                            exit(52)
                        else:
                            self.labels.setdefault(elem[0].text, i)
            elif opcode == 'JUMP':
                if len(elem) != 1:
                    exit(31)
                else:
                    if elem[0].get('type') != 'label' or elem[0].tag != 'arg1':
                        exit(31)
                    else:
                        self.checklabel(elem[0].text)
            elif opcode == 'JUMPIFEQ':
                if len(elem) != 3:
                    exit(31)
                else:
                    if elem[0].get('type') != 'label' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[2].get('type')) == None or elem[2].tag != 'arg3':
                        exit(31)
                    else:
                        self.checklabel(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
                        self.checksymbol(elem[2].get('type'), elem[2].text)
            elif opcode == 'JUMPIFNEQ':
                if len(elem) != 3:
                    exit(31)
                else:
                    if elem[0].get('type') != 'label' or elem[0].tag != 'arg1':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[1].get('type')) == None or elem[1].tag != 'arg2':
                        exit(31)
                    elif re.fullmatch(self.symb, elem[2].get('type')) == None or elem[2].tag != 'arg3':
                        exit(31)
                    else:
                        self.checklabel(elem[0].text)
                        self.checksymbol(elem[1].get('type'), elem[1].text)
                        self.checksymbol(elem[2].get('type'), elem[2].text)
            elif opcode == 'DPRINT':
                if len(elem) != 1:
                    exit(31)
                else:
                    if re.fullmatch(self.symb, elem[0].get('type')) == None or elem[0].tag != 'arg1':
                        exit(31)
                    else:
                        self.checksymbol(elem[0].get('type'), elem[0].text)
            elif opcode == 'BREAK':
                if len(elem) != 0:
                    exit(31)
                else:
                    pass
            else:
                exit(32)

    def iperform(self):
        x = 0
        while x < len(self.root):
            elem = self.root[x]
            x = x + 1
            opcode = elem.get('opcode')
            if opcode == 'CREATEFRAME':
                self.tempframe = {}

            elif opcode == 'PUSHFRAME':
                if self.tempframe == None:
                    exit(55)
                if self.localframe != None:
                    self.framearray.append(self.localframe)
                self.localframe = self.tempframe
                self.tempframe = None

            elif opcode == 'POPFRAME':
                if self.localframe == None:
                    exit(55)
                else:
                    self.tempframe = self.localframe
                    if len(self.framearray) != 0:
                        self.localframe = self.framearray.pop()
                    else:
                        self.localframe = None

            elif opcode == 'DEFVAR':
                match = re.fullmatch(self.var, elem[0].text)
                if match.group(1) == 'LF':
                    if self.localframe == None:
                        exit(55)
                    else:
                        self.localframe.setdefault(match.group(2))
                elif match.group(1) == 'GF':
                    if self.globalframe == None:
                        exit(55)
                    else:
                        self.globalframe.setdefault(match.group(2))
                elif match.group(1) == 'TF':
                    if self.tempframe == None:
                        exit(55)
                    else:
                        self.tempframe.setdefault(match.group(2))

            elif opcode == 'MOVE':
                self.setvar(elem[0].text, self.returnsymboltype(elem[1].get('type'), elem[1].text))

            elif opcode == 'WRITE':
                varvalue = self.returnsymboltype(elem[0].get('type'), elem[0].text)
                if isinstance(varvalue, bool) == False:

                    print(varvalue)
                else:
                    if varvalue == True:
                        print('true')
                    else:
                        print('false')

            elif opcode == 'ADD':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                if (isinstance(symb1value, int) == False or isinstance(symb1value, bool) == True) and elem[1].get(
                        'type') != 'var':
                    exit(52)
                elif (isinstance(symb1value, int) == False or isinstance(symb1value, bool) == True) and elem[1].get(
                        'type') == 'var':
                    exit(53)
                if (isinstance(symb2value, int) == False or isinstance(symb2value, bool) == True) and elem[2].get(
                        'type') != 'var':
                    exit(52)
                elif (isinstance(symb2value, int) == False or isinstance(symb2value, bool) == True) and elem[2].get(
                        'type') == 'var':
                    exit(53)
                symbsum = symb1value + symb2value
                self.setvar(elem[0].text, symbsum)

            elif opcode == 'SUB':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                if (isinstance(symb1value, int) == False or isinstance(symb1value, bool) == True) and elem[1].get(
                        'type') != 'var':
                    exit(52)
                elif (isinstance(symb1value, int) == False or isinstance(symb1value, bool) == True) and elem[1].get(
                        'type') == 'var':
                    exit(53)
                if (isinstance(symb2value, int) == False or isinstance(symb2value, bool) == True) and elem[2].get(
                        'type') != 'var':
                    exit(52)
                elif (isinstance(symb2value, int) == False or isinstance(symb2value, bool) == True) and elem[2].get(
                        'type') == 'var':
                    exit(53)
                symbsub = symb1value - symb2value
                self.setvar(elem[0].text, symbsub)

            elif opcode == 'MUL':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                if (isinstance(symb1value, int) == False or isinstance(symb1value, bool) == True) and elem[1].get(
                        'type') != 'var':
                    exit(52)
                elif (isinstance(symb1value, int) == False or isinstance(symb1value, bool) == True) and elem[1].get(
                        'type') == 'var':
                    exit(53)
                if (isinstance(symb2value, int) == False or isinstance(symb2value, bool) == True) and elem[2].get(
                        'type') != 'var':
                    exit(52)
                elif (isinstance(symb2value, int) == False or isinstance(symb2value, bool) == True) and elem[2].get(
                        'type') == 'var':
                    exit(53)
                symbmul = symb1value * symb2value
                self.setvar(elem[0].text, symbmul)

            elif opcode == 'IDIV':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                if (isinstance(symb1value, int) == False or isinstance(symb1value, bool) == True) and elem[1].get(
                        'type') != 'var':
                    exit(52)
                elif (isinstance(symb1value, int) == False or isinstance(symb1value, bool) == True) and elem[1].get(
                        'type') == 'var':
                    exit(53)
                if (isinstance(symb2value, int) == False or isinstance(symb2value, bool) == True) and elem[2].get(
                        'type') != 'var':
                    exit(52)
                elif (isinstance(symb2value, int) == False or isinstance(symb2value, bool) == True) and elem[2].get(
                        'type') == 'var':
                    exit(53)
                if symb2value == 0:
                    exit(57)
                else:
                    symbidiv = symb1value // symb2value
                    self.setvar(elem[0].text, symbidiv)

            elif opcode == 'AND':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                if isinstance(symb1value, bool) == False and elem[1].get('type') != 'var':
                    exit(52)
                elif isinstance(symb1value, bool) == False and elem[1].get('type') == 'var':
                    exit(53)
                if isinstance(symb2value, bool) == False and elem[2].get('type') != 'var':
                    exit(52)
                elif isinstance(symb2value, bool) == False and elem[2].get('type') == 'var':
                    exit(53)
                else:
                    symbandsymb = symb1value and symb2value
                    self.setvar(elem[0].text, symbandsymb)

            elif opcode == 'OR':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                if isinstance(symb1value, bool) == False and elem[1].get('type') != 'var':
                    exit(52)
                elif isinstance(symb1value, bool) == False and elem[1].get('type') == 'var':
                    exit(53)
                if isinstance(symb2value, bool) == False and elem[2].get('type') != 'var':
                    exit(52)
                elif isinstance(symb2value, bool) == False and elem[2].get('type') == 'var':
                    exit(53)
                else:
                    symborsymb = symb1value or symb2value
                    self.setvar(elem[0].text, symborsymb)

            elif opcode == 'NOT':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                if isinstance(symb1value, bool) == False and elem[1].get('type') != 'var':
                    exit(52)
                elif isinstance(symb1value, bool) == False and elem[1].get('type') == 'var':
                    exit(53)
                else:
                    notsymb = not symb1value
                    self.setvar(elem[0].text, notsymb)

            elif opcode == 'LT':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                if self.checksamesymbol(symb1value, symb2value) == False and elem[1].get('type') == 'var':
                    exit(53)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[2].get('type') == 'var':
                    exit(53)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[1].get('type') != 'var':
                    exit(52)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[2].get('type') != 'var':
                    exit(52)

                if isinstance(symb1value, int) == True and isinstance(symb2value, int) == True:
                    symbvalue = symb1value < symb2value
                elif isinstance(symb1value, bool) == True and isinstance(symb2value, bool) == True:
                    if symb1value == False and symb2value == False:
                        symbvalue = False
                    elif symb1value == False and symb2value == True:
                        symbvalue = True
                    elif symb1value == True and symb2value == False:
                        symbvalue = False
                    elif symb1value == True and symb2value == True:
                        symbvalue = False
                elif isinstance(symb1value, str) == True and isinstance(symb2value, str) == True:
                    symbvalue = symb1value < symb2value
                self.setvar(elem[0].text, symbvalue)

            elif opcode == 'GT':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                if self.checksamesymbol(symb1value, symb2value) == False and elem[1].get('type') == 'var':
                    exit(53)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[2].get('type') == 'var':
                    exit(53)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[1].get('type') != 'var':
                    exit(52)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[2].get('type') != 'var':
                    exit(52)

                if isinstance(symb1value, int) == True and isinstance(symb2value, int) == True:
                    symbvalue = symb1value > symb2value
                elif isinstance(symb1value, bool) == True and isinstance(symb2value, bool) == True:
                    if symb1value == False and symb2value == False:
                        symbvalue = False
                    elif symb1value == False and symb2value == True:
                        symbvalue = False
                    elif symb1value == True and symb2value == False:
                        symbvalue = True
                    elif symb1value == True and symb2value == True:
                        symbvalue = False
                elif isinstance(symb1value, str) == True and isinstance(symb2value, str) == True:
                    symbvalue = symb1value > symb2value
                self.setvar(elem[0].text, symbvalue)

            elif opcode == 'EQ':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                if self.checksamesymbol(symb1value, symb2value) == False and elem[1].get('type') == 'var':
                    exit(53)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[2].get('type') == 'var':
                    exit(53)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[1].get('type') != 'var':
                    exit(52)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[2].get('type') != 'var':
                    exit(52)

                if isinstance(symb1value, int) == True and isinstance(symb2value, int) == True:
                    symbvalue = symb1value == symb2value
                elif isinstance(symb1value, bool) == True and isinstance(symb2value, bool) == True:
                    if symb1value == False and symb2value == False:
                        symbvalue = True
                    elif symb1value == False and symb2value == True:
                        symbvalue = False
                    elif symb1value == True and symb2value == False:
                        symbvalue = False
                    elif symb1value == True and symb2value == True:
                        symbvalue = True
                elif isinstance(symb1value, str) == True and isinstance(symb2value, str) == True:
                    symbvalue = symb1value == symb2value
                self.setvar(elem[0].text, symbvalue)

            elif opcode == 'TYPE':
                if elem[1].get('type') != 'var':
                    symbvalue = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                else:
                    match = re.fullmatch(self.var, elem[1].text)
                    if match.group(1) == 'LF':
                        if self.localframe == None:
                            exit(55)
                        else:
                            if match.group(2) not in self.localframe:
                                exit(54)
                            else:
                                if self.localframe.get(match.group(2)) == None:
                                    symbvalue = None
                                else:
                                    symbvalue = self.localframe.get(match.group(2))
                    elif match.group(1) == 'GF':
                        if self.globalframe == None:
                            exit(55)
                        else:
                            if match.group(2) not in self.globalframe:
                                exit(54)
                            else:
                                if self.globalframe.get(match.group(2)) == None:
                                    symbvalue = None
                                else:
                                    symbvalue = self.globalframe.get(match.group(2))
                    elif match.group(1) == 'TF':
                        if self.tempframe == None:
                            exit(55)
                        else:
                            if match.group(2) not in self.tempframe:
                                exit(54)
                            else:
                                if self.tempframe.get(match.group(2)) == None:
                                    symbvalue = None
                                else:
                                    symbvalue = self.tempframe.get(match.group(2))

                if symbvalue == None:
                    symbvalue = ""
                elif isinstance(symbvalue, bool) == True:
                    symbvalue = 'bool'
                elif isinstance(symbvalue, int) == True:
                    symbvalue = 'int'
                elif isinstance(symbvalue, str) == True:
                    symbvalue = 'string'
                self.setvar(elem[0].text, symbvalue)

            elif opcode == 'JUMP':
                if elem[0].text not in self.labels:
                    exit(52)
                else:
                    x = self.labels.get(elem[0].text)

            elif opcode == 'JUMPIFEQ':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                if self.checksamesymbol(symb1value, symb2value) == False and elem[1].get('type') == 'var':
                    exit(53)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[2].get('type') == 'var':
                    exit(53)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[1].get('type') != 'var':
                    exit(52)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[2].get('type') != 'var':
                    exit(52)
                if symb1value == symb2value:
                    if elem[0].text not in self.labels:
                        exit(52)
                    else:
                        x = self.labels.get(elem[0].text)

            elif opcode == 'JUMPIFNEQ':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                if self.checksamesymbol(symb1value, symb2value) == False and elem[1].get('type') == 'var':
                    exit(53)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[2].get('type') == 'var':
                    exit(53)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[1].get('type') != 'var':
                    exit(52)
                elif self.checksamesymbol(symb1value, symb2value) == False and elem[2].get('type') != 'var':
                    exit(52)
                if symb1value != symb2value:
                    if elem[0].text not in self.labels:
                        exit(52)
                    else:
                        x = self.labels.get(elem[0].text)

            elif opcode == 'CONCAT':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                if isinstance(symb1value, str) == False and elem[1].get('type') != 'var':
                    exit(52)
                elif isinstance(symb1value, str) == False and elem[1].get('type') == 'var':
                    exit(53)
                if isinstance(symb2value, str) == False and elem[2].get('type') != 'var':
                    exit(52)
                elif isinstance(symb2value, str) == False and elem[2].get('type') == 'var':
                    exit(53)
                else:
                    symbvalue = symb1value + symb2value
                    self.setvar(elem[0].text, symbvalue)

            elif opcode == 'STRLEN':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                if isinstance(symb1value, str) == False and elem[1].get('type') != 'var':
                    exit(52)
                elif isinstance(symb1value, str) == False and elem[1].get('type') == 'var':
                    exit(53)
                symbvalue = len(symb1value)
                self.setvar(elem[0].text, symbvalue)

            elif opcode == 'INT2CHAR':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                if (isinstance(symb1value, int) == False or isinstance(symb1value, bool) == True) and elem[1].get(
                        'type') != 'var':
                    exit(52)
                elif (isinstance(symb1value, int) == False or isinstance(symb1value, bool) == True) and elem[1].get(
                        'type') == 'var':
                    exit(53)
                else:
                    if symb1value > -1 and symb1value < 1114111:
                        symb1value = chr(symb1value)
                        self.setvar(elem[0].text, symb1value)
                    else:
                        exit(58)

            elif opcode == 'CALL':
                if elem[0].text not in self.labels:
                    exit(52)
                else:
                    self.callarray.append(x)
                    x = self.labels.get(elem[0].text)

            elif opcode == 'RETURN':
                if len(self.callarray) != 0:
                    x = self.callarray.pop()
                else:
                    exit(56)

            elif opcode == 'PUSHS':
                symb1value = self.returnsymboltype(elem[0].get('type'), elem[0].text)
                self.dataarray.append(symb1value)

            elif opcode == 'POPS':
                if len(self.dataarray) != 0:
                    varvalue = self.dataarray.pop()
                    self.setvar(elem[0].text, varvalue)
                else:
                    exit(56)

            elif opcode == 'STRI2INT':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                if (isinstance(symb1value, str) == False) and elem[1].get('type') != 'var':
                    exit(52)
                elif (isinstance(symb1value, str) == False) and elem[1].get('type') == 'var':
                    exit(53)
                if (isinstance(symb2value, int) == False or isinstance(symb2value, bool) == True) and elem[2].get(
                        'type') != 'var':
                    exit(52)
                elif (isinstance(symb2value, int) == False or isinstance(symb2value, bool) == True) and elem[2].get(
                        'type') == 'var':
                    exit(53)
                else:
                    if (symb2value < len(symb1value) and symb2value >= 0):
                        varvalue = ord(symb1value[symb2value])
                        self.setvar(elem[0].text, varvalue)
                    else:
                        exit(58)

            elif opcode == 'GETCHAR':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                if (isinstance(symb1value, str) == False) and elem[1].get('type') != 'var':
                    exit(52)
                elif (isinstance(symb1value, str) == False) and elem[1].get('type') == 'var':
                    exit(53)
                if isinstance(symb2value, int) == False and elem[2].get('type') != 'var':
                    exit(52)
                elif isinstance(symb2value, int) == False and elem[2].get('type') == 'var':
                    exit(53)
                else:
                    if (symb2value >= 0 and symb2value < len(symb1value)):
                        varvalue = symb1value[symb2value]
                        self.setvar(elem[0].text, varvalue)
                    else:
                        exit(58)

            elif opcode == 'SETCHAR':
                symb1value = self.returnsymboltype(elem[1].get('type'), elem[1].text)
                symb2value = self.returnsymboltype(elem[2].get('type'), elem[2].text)
                varvalue = self.returnsymboltype('var', elem[0].text)
                if (isinstance(symb1value, int) == False) and elem[1].get('type') != 'var':
                    exit(52)
                elif (isinstance(symb1value, int) == False) and elem[1].get('type') == 'var':
                    exit(53)
                if isinstance(symb2value, str) == False and elem[2].get('type') != 'var':
                    exit(52)
                elif isinstance(symb2value, str) == False and elem[2].get('type') == 'var':
                    exit(53)
                if (isinstance(varvalue, str) == False):
                    exit(52)
                else:
                    if symb2value == '' or ((symb1value < 0) or symb1value >= (len(varvalue))):
                        exit(58)
                    else:
                        tempvar = varvalue[0:symb1value]
                        if symb1value + 1 < len(varvalue):
                            varvalue = tempvar + symb2value[0] + varvalue[symb1value + 1:]
                        else:
                            varvalue = tempvar + symb2value[0]
                        self.setvar(elem[0].text, varvalue)
            elif opcode == 'READ':
                value = input()
                if elem[1].text == 'int':
                    try:
                        varvalue = int(value)
                    except Exception as e:
                        varvalue = 0
                elif elem[1].text == 'string':
                    varvalue = value
                elif elem[1].text == 'bool' and value.lower() == 'true':
                    varvalue = True
                else:
                    varvalue = False
                self.setvar(elem[0].text, varvalue)


Interpet()